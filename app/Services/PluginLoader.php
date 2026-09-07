<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;
use flight\net\Router;
use Enlivenapp\FlightShield\Middlewares\GroupMiddleware;
use Pubvana\Plugins\Blog\Controllers\BlogPublicController;
use Pubvana\Plugins\Pages\Controllers\PagesPublicController;

/**
 * PluginLoader - Discovers, configures, and boots all plugins.
 *
 * Handles two sources of plugins:
 *
 *   1. Local plugins (plugins/) - pubvana.json manifests, own Controllers/Views/etc.
 *   2. Vendor packages (vendor/) - Composer packages with type "flightphp-*"
 *      or "pubvana-plugin", auto-discovered from vendor/composer/installed.json.
 *
 * For each enabled plugin/package:
 *   1. Registers its Views/ directory with PluginView for template resolution
 *   2. Loads config from Config/Config.php (vendor) or pubvana.json (local)
 *   3. Wraps Routes.php/AdminRoutes.php in route groups with PluginViewContextMiddleware
 *   4. Calls Plugin::register() for custom setup
 *
 * Routes are auto-prefixed: public routes get the routePrepend, admin routes get /admin/.
 * PluginViewContextMiddleware wraps each route group for template resolution.
 *
 * Enable/disable is database-backed (plugin_state table). Core-only migrations
 * run first (so plugin_state exists), then migrations + seeds run for ENABLED
 * plugins only; a disabled plugin's files are never loaded or executed. The
 * same gated config is exposed via getMigrationConfig() for CLI tooling.
 *
 * @package Pubvana\Services
 */
class PluginLoader
{
    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;

    /** @var Router The FlightPHP router */
    protected Router $router;

    /** @var string Absolute path to the plugins/ directory (local plugins) */
    protected string $pluginPath;

    /** @var string Absolute path to the vendor/ directory (Composer packages) */
    protected string $vendorPath;

    /** @var array<string, mixed> Plugin config the app passes in (enabled, priority, etc.) */
    protected array $enabledPlugins;

    /** @var array<string, PluginInterface> Loaded plugin instances, keyed by plugin ID */
    protected array $loaded = [];

    /** @var bool Whether loadPlugins() has already completed in this process */
    protected bool $pluginsLoaded = false;

    /** @var array<string, array<string, mixed>> Plugin config arrays, keyed by plugin ID */
    protected array $pluginConfigs = [];

    /** @var array<string, array<string, mixed>> Plugin manifest data, keyed by plugin ID (local plugins only) */
    protected array $pluginManifests = [];

    /** @var array<string, string> Vendor plugin root paths keyed by package name */
    protected array $vendorRoots = [];

    /** @var array<string, string> Base PSR-4 namespace per vendor package */
    protected array $vendorNamespaces = [];

    /** @var array<string, array{enabled: bool, priority: int, required: bool}> Resolved plugin_state rows by plugin ID */
    protected array $pluginStates = [];

    /** @var array<string, int> Core plugins that can never be disabled, keyed to their default priority */
    protected array $requiredPlugins = [
        'enlivenapp/flight-sessions' => 2,
        'enlivenapp/flight-shield'    => 5,
        'enlivenapp/flight-csrf'      => 10,
    ];

    /** @var array<string, array<string, mixed>> Discovered plugin info keyed by plugin ID (local + vendor) */
    protected array $discoveredById = [];

    /**
     * @param Engine<object>       $app            The FlightPHP app instance
     * @param Router               $router         The FlightPHP router
     * @param string               $pluginPath     Absolute path to the plugins/ directory
     * @param string               $vendorPath     Absolute path to the vendor/ directory
     * @param array<string, mixed> $enabledPlugins Plugin config the app passes in
     */
    public function __construct(Engine $app, Router $router, string $pluginPath, string $vendorPath, array $enabledPlugins = [])
    {
        $this->app = $app;
        $this->router = $router;
        $this->pluginPath = rtrim($pluginPath, DIRECTORY_SEPARATOR);
        $this->vendorPath = rtrim($vendorPath, DIRECTORY_SEPARATOR);
        $this->enabledPlugins = $enabledPlugins;
    }

    /*
    |--------------------------------------------------------------------------
    | Main Load Flow
    |--------------------------------------------------------------------------
    */

    /**
     * Discover and load all enabled plugins (local + vendor).
     *
     * Order:
     *   1. Core-only database migrations (app/Database) - creates the
     *      plugin_state table before anything reads it
     *   2. Discover local plugins (plugins directories with pubvana.json)
     *   3. Discover vendor packages (vendor/composer/installed.json, type: flightphp-* or pubvana-plugin)
     *   4. Upsert a plugin_state row per discovered plugin (first-discovery defaults)
     *   5. Run migrations + seeds for ENABLED plugins only - disabled plugins'
     *      directories are never scanned, so their code never executes
     *   6. Sort by priority (DB-backed), load each enabled plugin
     *
     * @return array<string, PluginInterface> Loaded plugin instances keyed by plugin ID
     */
    public function loadPlugins(): array
    {
        // Re-entry guard: CLI tools (runway) may require services.php and then
        // include the front controller in one process, booting plugins twice.
        // Migrations, Plugin::register(), and session starts are not idempotent.
        if ($this->pluginsLoaded) {
            return $this->loaded;
        }

        // Discover from both sources (filesystem + installed.json only, no DB).
        $local = $this->discoverLocal();
        $vendor = $this->discoverVendor();
        $all = array_merge($local, $vendor);
        $this->discoveredById = $all;

        // Tier 1: FOUNDATION packages first — migrations + seeds unconditional.
        // These are the required vendor packages (enlivenapp/* flightphp-foundation,
        // pubvana/* pubvana-foundation). Their migrations create the tables core
        // seeds depend on (e.g. Shield's auth_permissions), which is why they run
        // before core.
        // Migration checks run only where they can do work: fresh installs
        // (no marker file), CLI runway, and admin requests. Public page
        // requests and cron runs skip all three migration tiers entirely.
        $foundation = array_filter(
            $all,
            fn($info, $pluginId) => $this->isFoundationPackage((string) $pluginId, $info),
            ARRAY_FILTER_USE_BOTH
        );
        $runMigrations = $this->shouldRunMigrationsOnThisRequest();

        if ($runMigrations) {
            $this->runFoundationMigrations($foundation);
        }

        // Tier 2: CORE migrations + seeds. Creates plugin_state, settings, and
        // every other core table before discovery/sync reads the database.
        if ($runMigrations) {
            $this->runCoreMigrations();
        }

        // Tier 3: Persist first-discovery defaults to plugin_state and cache the
        // resolved enabled/priority/required state for this request.
        $this->syncPluginStates($all);

        // Tier 4: Migrations + seeds for ENABLED non-foundation plugins only.
        // Disabled plugin directories are never scanned here, so their migration
        // and seed files are never loaded — that is the enable/disable pause.
        if ($runMigrations) {
            $this->runPluginMigrations($all);
        }

        // DB-backed config overrides. The settings table now exists (core
        // migrations), and plugins have not registered yet, so admin-stored
        // Shield toggles can fold into the plugin's config before Shield's
        // Plugin::register() snapshots it into the auth service. Anything
        // stored under the 'Shield' settings namespace lands in the
        // flight-shield config; see applyShieldSettingOverrides().
        $this->applyShieldSettingOverrides();

        // Sort by priority (lower = earlier), from plugin_state
        uasort($all, fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        foreach ($all as $pluginId => $info) {
            if (!$this->isEnabled($pluginId)) {
                continue;
            }

            if ($info['source'] === 'vendor') {
                $this->loadVendorPackage($pluginId, $info);
            } else {
                $this->loadLocalPlugin($pluginId, $info);
            }
        }

        $this->pluginsLoaded = true;

        return $this->loaded;
    }

    /**
     * Fold admin-stored Shield toggles into the shield plugin config.
     *
     * The Login admin page (Admin > Settings > Login) stores boolean
     * settings in the settings store under the 'Shield' namespace. This
     * method translates them onto the flight-shield config keys that
     * Shield reads at boot:
     *
     *   Shield.email_2fa          -> actions.login            (Email2FA or null)
     *   Shield.email_activation   -> actions.register         (EmailActivator or null)
     *   Shield.allow_registration -> allow_registration
     *   Shield.magic_link         -> allow_magic_link
     *   Shield.remember_me        -> session.allow_remembering
     *
     * Only stored rows apply here (defaults stay in app/config/shield.php),
     * so unchecking nothing stores nothing and package defaults rule until
     * an admin saves the page. Runs after core migrations (settings table
     * exists) and before the plugin registration loop (Shield has not
     * snapshotted its config into the auth service yet).
     */
    protected function applyShieldSettingOverrides(): void
    {
        if (!array_key_exists('enlivenapp/flight-shield', $this->enabledPlugins)) {
            return;
        }

        try {
            $stored = $this->app->settings()->all('Shield');
        } catch (\Throwable $e) {
            // Settings store unreadable (fresh install edge). Package and
            // app config file stand in as-is.
            return;
        }

        if ($stored === []) {
            return;
        }

        $overrides = [];
        foreach ($stored as $key => $value) {
            // all() returns full settings keys ('Shield.email_2fa'); strip
            // the namespace for the translation switch below.
            $short = str_starts_with($key, 'Shield.') ? substr($key, strlen('Shield.')) : $key;

            switch ($short) {
                case 'email_2fa':
                    $overrides['actions']['login'] = $value === true
                        ? \Enlivenapp\FlightShield\Authentication\Actions\Email2FA::class
                        : null;
                    break;
                case 'email_activation':
                    $overrides['actions']['register'] = $value === true
                        ? \Enlivenapp\FlightShield\Authentication\Actions\EmailActivator::class
                        : null;
                    break;
                case 'allow_registration':
                    $overrides['allow_registration'] = (bool) $value;
                    break;
                case 'magic_link':
                    $overrides['allow_magic_link'] = (bool) $value;
                    break;
                case 'remember_me':
                    $overrides['session']['allow_remembering'] = (bool) $value;
                    break;
            }
        }

        if ($overrides !== []) {
            $this->enabledPlugins['enlivenapp/flight-shield'] = array_replace_recursive(
                $this->enabledPlugins['enlivenapp/flight-shield'],
                $overrides
            );
        }
    }

    /**
     * Load a local plugin (from plugins/ directory).
     *
     * Local plugins have:
     *   - pubvana.json manifest (name, provides, etc.)
     *   - Optional Plugin.php for custom setup
     *   - Routes.php / AdminRoutes.php at plugin root
     *   - Controllers/, Views/, Models/, Database/ directories
     *
     * @param string               $pluginId Plugin ID (e.g. 'pubvana/blog')
     * @param array<string, mixed> $info     Plugin info from discoverLocal()
     */
    protected function loadLocalPlugin(string $pluginId, array $info): void
    {
        $pluginDir = $this->pluginPath . DIRECTORY_SEPARATOR . $info['folder'];
        $this->loadLocalPluginConfig($pluginId, $pluginDir, $info['config']);
        $this->pluginManifests[$pluginId] = $info['manifest'];

        $this->registerPluginViewPath($pluginId, $pluginDir);
        $this->registerPluginHooks($pluginId, $info['manifest']);
        $this->loadPluginRoutes($pluginId, $pluginDir, $this->pluginConfigs[$pluginId]);

        $this->loadPluginClass($pluginId, $pluginDir . DIRECTORY_SEPARATOR . 'Plugin.php', $info['namespace'], $this->pluginConfigs[$pluginId]);
    }

    /**
     * Load a local plugin's Config/Config.php and merge with app overrides.
     *
     * Mirrors the vendor path: reads Config.php for routePrepend,
     * configPrepend, and other settings, then merges any app-level
     * overrides from the plugins config array.
     *
     * @param string               $pluginId  Plugin ID
     * @param string               $pluginDir Plugin root directory
     * @param array<string, mixed> $appConfig Config from enabledPlugins array
     */
    protected function loadLocalPluginConfig(string $pluginId, string $pluginDir, array $appConfig): void
    {
        $configFile = $pluginDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Config.php';

        $config = [];
        if (file_exists($configFile)) {
            $result = require $configFile;
            if (is_array($result)) {
                $config = $result;
            }
        }

        $overrides = $appConfig;
        unset($overrides['enabled'], $overrides['priority']);
        if (!empty($overrides)) {
            $config = array_replace_recursive($config, $overrides);
        }

        $this->pluginConfigs[$pluginId] = $config;
    }

    /**
     * Load a vendor package (from vendor/ directory).
     *
     * Vendor packages have:
     *   - src/Config/Config.php (config values, routePrepend, configPrepend)
     *   - src/Config/Routes.php (optional, auto-wrapped in route group)
     *   - src/Config/AdminRoutes.php (optional, auto-wrapped in /admin group)
     *   - src/Plugin.php (optional, implements PluginInterface)
     *   - src/Views/ (optional, registered for template resolution)
     *
     * @param string               $pluginId Package name (e.g. 'enlivenapp/flight-shield')
     * @param array<string, mixed> $info     Package info from discoverVendor()
     */
    protected function loadVendorPackage(string $pluginId, array $info): void
    {
        $root = $this->vendorRoots[$pluginId] ?? null;
        if ($root === null) {
            return;
        }

        $this->registerPluginViewPath($pluginId, $root);
        $this->loadVendorConfigDir($pluginId, $root);

        $pluginFile = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Plugin.php';
        $this->loadPluginClass($pluginId, $pluginFile, $info['namespace'], $this->pluginConfigs[$pluginId] ?? []);
    }

    /**
     * Load a Plugin.php class if it exists and has a register() method.
     *
     * Works for both local plugins and vendor packages. Checks for:
     *   1. PluginInterface implementation (our local plugins)
     *   2. A public register(Engine, Router, array) method (vendor packages)
     *
     * @param string               $pluginId  Plugin/package ID
     * @param string               $pluginFile Absolute path to Plugin.php
     * @param string               $namespace PSR-4 namespace prefix
     * @param array<string, mixed> $config    Merged config
     */
    protected function loadPluginClass(string $pluginId, string $pluginFile, string $namespace, array $config): void
    {
        if (!file_exists($pluginFile)) {
            return;
        }

        require_once($pluginFile);
        $className = rtrim($namespace, '\\') . '\\Plugin';

        if (!class_exists($className)) {
            return;
        }

        // Accept if it implements our interface OR has a compatible register() method
        $implementsInterface = is_a($className, PluginInterface::class, true);
        $hasRegisterMethod = method_exists($className, 'register');

        if ($implementsInterface || $hasRegisterMethod) {
            $plugin = new $className();
            $plugin->register($this->app, $this->router, $config);
            if ($plugin instanceof PluginInterface) {
                $this->loaded[$pluginId] = $plugin;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Discovery
    |--------------------------------------------------------------------------
    */

    /**
     * Scan plugins/ directory for pubvana.json manifests.
     *
     * Each folder must have a pubvana.json with at minimum a "name" key.
     * The name becomes the plugin ID (e.g. 'pubvana/blog').
     *
     * @return array<string, array<string, mixed>> Plugin info arrays keyed by plugin ID
     */
    public function discoverLocal(): array
    {
        $plugins = [];
        foreach (glob($this->pluginPath . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $folder = basename($dir);
            $manifestFile = $dir . DIRECTORY_SEPARATOR . 'pubvana.json';
            if (!is_file($manifestFile)) {
                continue;
            }

            $raw = file_get_contents($manifestFile);
            if ($raw === false) {
                continue;
            }

            $manifest = json_decode($raw, true);
            if (!is_array($manifest)) {
                continue;
            }

            $pluginId = $manifest['name'] ?? $folder;
            $config = $this->enabledPlugins[$pluginId] ?? ['enabled' => true, 'priority' => 50];

            $plugins[$pluginId] = [
                'source'    => 'local',
                'folder'    => $folder,
                'manifest'  => $manifest,
                'config'    => $config,
                'priority'  => $config['priority'] ?? 50,
                'namespace' => $manifest['namespace'] ?? 'Pubvana\\Plugins\\' . ucfirst($folder),
                'version'   => $manifest['semver'] ?? $manifest['version'] ?? '',
            ];
        }
        return $plugins;
    }

    /**
     * Discover Composer packages with type "flightphp-*" or "pubvana-plugin"
     * from installed.json.
     *
     * Reads vendor/composer/installed.json and filters for packages whose
     * "type" starts with "flightphp-" or equals "pubvana-plugin". Resolves
     * the plugin class from the first PSR-4 namespace + "\Plugin".
     *
     * @return array<string, array<string, mixed>> Package info arrays keyed by package name
     */
    public function discoverVendor(): array
    {
        $installedFile = $this->vendorPath
            . DIRECTORY_SEPARATOR . 'composer'
            . DIRECTORY_SEPARATOR . 'installed.json';

        if (!file_exists($installedFile)) {
            return [];
        }

        $raw = file_get_contents($installedFile);
        if ($raw === false) {
            return [];
        }

        $installed = json_decode($raw, true);
        // Composer 2 wraps in "packages" key
        $packages = $installed['packages'] ?? $installed;

        $discovered = [];
        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            if ($name === '') {
                continue;
            }

            // Resolve plugin root path
            $root = $this->vendorPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);
            if (!is_dir($root)) {
                continue;
            }

            // Validate: must be within vendor dir (no symlinks escaping)
            $realPath = realpath($root);
            $realVendor = realpath($this->vendorPath);
            if ($realPath === false || $realVendor === false || !str_starts_with($realPath, $realVendor . DIRECTORY_SEPARATOR)) {
                continue;
            }

            // Type is authoritative from the package's own composer.json, not the
            // installed.json snapshot (which composer caches at install time and
            // does not refresh on type-only edits). installed.json still supplies
            // the autoload/namespace mapping.
            $pkgComposer = json_decode((string) file_get_contents($root . DIRECTORY_SEPARATOR . 'composer.json'), true);
            $type = is_array($pkgComposer) ? ($pkgComposer['type'] ?? 'library') : ($package['type'] ?? 'library');

            $isPluginPackage = str_starts_with($type, 'flightphp-')
                || $type === 'pubvana-plugin'
                || $type === 'pubvana-foundation';
            if (!$isPluginPackage) {
                continue;
            }

            // Resolve namespace from PSR-4 autoload
            $autoload = $package['autoload']['psr-4'] ?? [];
            if (empty($autoload)) {
                continue;
            }
            $namespace = rtrim(array_key_first($autoload), '\\');

            $this->vendorRoots[$name] = $root;
            $this->vendorNamespaces[$name] = $namespace;

            // Check for config overrides in the plugins array
            $config = $this->enabledPlugins[$name] ?? ['enabled' => true, 'priority' => 50];

            $discovered[$name] = [
                'source'    => 'vendor',
                'type'      => $type,
                'config'    => $config,
                'priority'  => $config['priority'] ?? 50,
                'namespace' => $namespace,
                'version'   => $package['version'] ?? '',
            ];
        }

        return $discovered;
    }

    /*
    |--------------------------------------------------------------------------
    | View Registration
    |--------------------------------------------------------------------------
    */

    /**
     * Register a plugin's Views/ directory with the PluginView system.
     *
     * For local plugins: looks for {pluginDir}/Views/
     * For vendor packages: looks for {vendorRoot}/src/Views/
     *
     * @param string $pluginId Plugin/package ID
     * @param string $root     Plugin root directory (local or vendor)
     */
    protected function registerPluginViewPath(string $pluginId, string $root): void
    {
        $view = $this->app->view();
        if (!$view instanceof PluginView) {
            return;
        }

        // Vendor packages store Views under src/Views/
        $viewsPath = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Views';
        if (!is_dir($viewsPath)) {
            // Local plugins store Views directly under the plugin root
            $viewsPath = $root . DIRECTORY_SEPARATOR . 'Views';
        }

        if (is_dir($viewsPath)) {
            $view->addPluginPath($pluginId, $viewsPath);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Local Plugin Hooks (from pubvana.json)
    |--------------------------------------------------------------------------
    */

    /**
     * Register a local plugin's hooks from its pubvana.json manifest.
     *
     * Reads the "provides" section and registers each item with the
     * ExtensionRegistry.
     *
     * @param string               $pluginId Plugin ID
     * @param array<string, mixed> $manifest Plugin manifest from pubvana.json
     */
    protected function registerPluginHooks(string $pluginId, array $manifest): void
    {
        $adext = $this->app->adext();
        $provides = $manifest['provides'] ?? [];

        if (!empty($provides['admin.menu'])) {
            foreach ($provides['admin.menu'] as $slot => $items) {
                foreach ($items as $key => $item) {
                    $adext->register('admin.menu', $slot, $pluginId . '.' . $key, $item);
                }
            }
        }

        if (!empty($provides['admin.dashboard'])) {
            $dashboard = $provides['admin.dashboard'];
            if (!empty($dashboard['cards'])) {
                foreach ($dashboard['cards'] as $key => $card) {
                    $adext->register('admin.dashboard', 'cards', $pluginId . '.' . $key, $card);
                }
            }
            if (!empty($dashboard['sections'])) {
                foreach ($dashboard['sections'] as $key => $section) {
                    $adext->register('admin.dashboard', 'sections', $pluginId . '.' . $key, $section);
                }
            }
        }

        if (!empty($provides['admin.css'])) {
            foreach ($provides['admin.css'] as $key => $css) {
                $adext->register('admin.css', 'default', $pluginId . '.' . $key, $css);
            }
        }
        if (!empty($provides['admin.js'])) {
            foreach ($provides['admin.js'] as $key => $js) {
                $adext->register('admin.js', 'default', $pluginId . '.' . $key, $js);
            }
        }

        if (!empty($provides['public.nav'])) {
            foreach ($provides['public.nav'] as $slot => $items) {
                foreach ($items as $key => $item) {
                    $adext->register('public.nav', $slot, $pluginId . '.' . $key, $item);
                }
            }
        }

        if (!empty($provides['public.css'])) {
            foreach ($provides['public.css'] as $key => $css) {
                $adext->register('public.css', 'default', $pluginId . '.' . $key, $css);
            }
        }
        if (!empty($provides['public.js'])) {
            foreach ($provides['public.js'] as $key => $js) {
                $adext->register('public.js', 'default', $pluginId . '.' . $key, $js);
            }
        }

        if (!empty($provides['block'])) {
            foreach ($provides['block'] as $slot => $blocks) {
                foreach ($blocks as $key => $block) {
                    $adext->register('block', $slot, $pluginId . '.' . $key, $block);
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Route Loading
    |--------------------------------------------------------------------------
    */

    /**
     * Load a local plugin's public and admin routes.
     *
     * Public routes wrapped in /{routePrepend}/ group.
     * Admin routes wrapped in /admin/ group.
     * Both use PluginViewContextMiddleware for template resolution.
     *
     * @param string               $pluginId  Plugin ID
     * @param string               $pluginDir Absolute path to plugin root
     * @param array<string, mixed> $config    Plugin config
     */
    protected function loadPluginRoutes(string $pluginId, string $pluginDir, array $config): void
    {
        $routePrepend = $config['routePrepend'] ?? $this->deriveRoutePrepend($pluginId);
        $configPrepend = $config['configPrepend'] ?? $this->deriveConfigPrepend($pluginId);
        $viewMiddleware = new PluginViewContextMiddleware($this->app, $pluginId);

        $routesFile = $pluginDir . DIRECTORY_SEPARATOR . 'Routes.php';
        if (file_exists($routesFile)) {
            $this->wrapRouteGroup('/' . $routePrepend, $routesFile, $configPrepend, $viewMiddleware);
        }

        $adminRoutesFile = $pluginDir . DIRECTORY_SEPARATOR . 'AdminRoutes.php';
        if (file_exists($adminRoutesFile)) {
            $this->wrapRouteGroup('/admin', $adminRoutesFile, $configPrepend, $viewMiddleware);
        }
    }

    /**
     * Load a vendor package's Config/ directory.
     *
     * Reads Config.php for settings and prepend values, then wraps
     * Routes.php and AdminRoutes.php in route groups with middleware.
     *
     * The Config.php return array can set:
     *   - 'routePrepend'  => URL prefix for public routes (default: derived from package name)
     *   - 'configPrepend' => app key prefix for config storage (default: derived from package name)
     *   - Any other keys are stored as the package's config
     *
     * @param string $pluginId Package name (e.g. 'enlivenapp/flight-shield')
     * @param string $root     Absolute path to package root
     */
    protected function loadVendorConfigDir(string $pluginId, string $root): void
    {
        $configDir = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Config';
        if (!is_dir($configDir)) {
            return;
        }

        $app = $this->app;
        $router = $this->router;

        // 1. Load Config.php - get settings and prepend overrides
        $config = [];
        $configFile = $configDir . DIRECTORY_SEPARATOR . 'Config.php';
        if (file_exists($configFile)) {
            $result = require $configFile;
            if (is_array($result)) {
                $config = $result;
            }
        }

        $configPrepend = $config['configPrepend'] ?? $this->deriveConfigPrepend($pluginId);
        $routePrepend = $config['routePrepend'] ?? $this->deriveRoutePrepend($pluginId);

        // Merge app-level config overrides
        $appOverrides = $this->enabledPlugins[$pluginId] ?? [];
        unset($appOverrides['enabled'], $appOverrides['priority']);
        if (!empty($appOverrides)) {
            $config = array_replace_recursive($config, $appOverrides);
        }

        // Store config on $app under the prefixed key
        if (!empty($config)) {
            $app->set($configPrepend, $config);
        }
        $this->pluginConfigs[$pluginId] = $config;

        // 2. Routes - auto-wrapped in prefix group with plugin view context
        $viewMiddleware = new PluginViewContextMiddleware($app, $pluginId);

        $routesFile = $configDir . DIRECTORY_SEPARATOR . 'Routes.php';
        if (file_exists($routesFile)) {
            $this->wrapRouteGroup('/' . $routePrepend, $routesFile, $configPrepend, $viewMiddleware);
        }

        // 2b. Admin Routes - auto-wrapped in /admin prefix
        $adminRoutesFile = $configDir . DIRECTORY_SEPARATOR . 'AdminRoutes.php';
        if (file_exists($adminRoutesFile)) {
            $this->wrapRouteGroup('/admin', $adminRoutesFile, $configPrepend, $viewMiddleware);
        }

        // 3. All other PHP files in Config/ (except handled ones)
        $handled = ['Config.php', 'Services.php', 'Routes.php', 'AdminRoutes.php'];
        foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            if (!in_array(basename($file), $handled, true)) {
                require $file;
            }
        }
    }

    /**
     * Wrap a routes file in a Flight router group.
     *
     * Sets $router and $app variables in scope for the routes file,
     * which is the convention that flight-shield and other vendor
     * packages expect.
     *
     * @param string                              $prefix       Route group prefix (e.g. '/auth')
     * @param string                              $routesFile   Absolute path to Routes.php
     * @param string                              $configPrepend Config key prefix
     * @param PluginViewContextMiddleware|null     $viewMiddleware Middleware for template context
     */
    protected function wrapRouteGroup(
        string $prefix,
        string $routesFile,
        string $configPrepend,
        ?PluginViewContextMiddleware $viewMiddleware = null
    ): void {
        $app = $this->app;
        $middleware = $viewMiddleware !== null ? [$viewMiddleware] : [];

        // Gate every plugin admin route behind an admin/superadmin login.
        if ($prefix === '/admin') {
            $middleware[] = new GroupMiddleware($app, 'admin', 'superadmin');
        }

        $this->router->group($prefix, function (Router $router) use ($app, $routesFile, $configPrepend) {
            // require_once: route files register handlers as side effects;
            // a second include in one process would duplicate every route.
            require_once $routesFile;
        }, $middleware);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Derive the config key prefix from a plugin/package ID.
     * e.g. 'pubvana/blog' becomes 'pubvana.blog'
     *      'enlivenapp/flight-shield' becomes 'enlivenapp.flight-shield'
     *
     * @param string $pluginId Plugin/package ID
     * @return string Config key prefix
     */
    protected function deriveConfigPrepend(string $pluginId): string
    {
        return str_replace('/', '.', $pluginId);
    }

    /**
     * Derive the route prefix from a plugin/package ID.
     * e.g. 'pubvana/blog' becomes 'pubvana_blog'
     *      'enlivenapp/flight-shield' becomes 'enlivenapp_flight_shield'
     *
     * @param string $pluginId Plugin/package ID
     * @return string Route prefix
     */
    protected function deriveRoutePrepend(string $pluginId): string
    {
        return str_replace(['/', '-'], '_', $pluginId);
    }

    /**
     * Get the public route prefix for a plugin.
     *
     * @param string $pluginId Plugin ID (e.g. 'pubvana/blog')
     * @return string Route prefix with leading slash (e.g. '/pubvana_blog')
     */
    public function routePrefix(string $pluginId): string
    {
        $config = $this->pluginConfigs[$pluginId] ?? [];
        $prefix = $config['routePrepend'] ?? $this->deriveRoutePrepend($pluginId);
        return '/' . ltrim($prefix, '/');
    }

    /**
     * Check if a plugin is enabled.
     *
     * The source of truth is the plugin_state table (DB-backed). A plugin is
     * only loadable when its plugin_state row has enabled = true. Fallback to
     * the app config array (plugins) covers offline/CLI edge cases where the
     * database is unavailable.
     *
     * @param string $pluginId Plugin ID
     * @return bool True if enabled
     */
    public function isEnabled(string $pluginId): bool
    {
        if (isset($this->pluginStates[$pluginId])) {
            return (bool) $this->pluginStates[$pluginId]['enabled'];
        }

        $settings = $this->enabledPlugins[$pluginId] ?? [];
        return !empty($settings['enabled']);
    }

    /**
     * Check whether a plugin is a required part of the core stack.
     *
     * Required plugins (sessions/shield/csrf) cannot be disabled via the admin
     * Plugins page. The flag is a safety invariant, not a permission: it holds
     * for every user, superadmin included.
     *
     * @param string $pluginId Plugin ID
     * @return bool True if required
     */
    public function isRequired(string $pluginId): bool
    {
        if (isset($this->pluginStates[$pluginId])) {
            return (bool) $this->pluginStates[$pluginId]['required'];
        }

        return $this->isFoundationPackage($pluginId, $this->foundationInfo($pluginId) ?? []);
    }

    /**
     * Whether a package is part of the foundation tier.
     *
     * Foundation packages run their migrations + seeds before everything else
     * and can never be disabled. Two gates must BOTH hold:
     *   - composer type is 'flightphp-foundation' (enlivenapp/*) or
     *     'pubvana-foundation' (pubvana/*)
     *   - the package name lives under a trusted namespace (enlivenapp/ or pubvana/)
     *
     * Type alone is spoofable; namespace alone is just a normal plugin. Both are
     * required so a third party cannot self-declare as foundation.
     *
     * @param string               $pluginId Plugin/package ID
     * @param array<string, mixed> $info     Plugin info array (source, type)
     * @return bool True if foundation
     */
    public function isFoundationPackage(string $pluginId, array $info): bool
    {
        $trusted = str_starts_with($pluginId, 'enlivenapp/')
            || str_starts_with($pluginId, 'pubvana/');

        if (!$trusted) {
            return false;
        }

        $type = $info['type'] ?? null;
        return $type === 'flightphp-foundation' || $type === 'pubvana-foundation';
    }

    /**
     * Look up the discovered info for a plugin (used before plugin_states sync).
     *
     * @param string $pluginId Plugin/package ID
     * @return array<string, mixed>|null Plugin info array, or null when not discovered
     */
    protected function foundationInfo(string $pluginId): ?array
    {
        return $this->discoveredById[$pluginId] ?? null;
    }

    /**
     * Get the resolved plugin_state row for a plugin.
     *
     * @param string $pluginId Plugin ID
     * @return array{enabled: bool, priority: int, required: bool}|null
     */
    public function getPluginState(string $pluginId): ?array
    {
        return $this->pluginStates[$pluginId] ?? null;
    }

    /** @return array<string, PluginInterface> Loaded plugin instances */
    public function getLoaded(): array
    {
        return $this->loaded;
    }

    /**
     * Get a plugin's manifest data from pubvana.json.
     *
     * @param string $pluginId Plugin ID
     * @return array<string, mixed>|null Manifest data, or null if not found
     */
    public function getManifest(string $pluginId): ?array
    {
        return $this->pluginManifests[$pluginId] ?? null;
    }

    /**
     * Dispatch the homepage based on CMS.homepageType setting.
     *
     *   blog  → blog index (default)
     *   pages → static page looked up from CMS.homepagePageId
     *
     * Renders the chosen content directly on "/" (200) rather than
     * redirecting, which search engines index more favorably than a
     * forward from the site root.
     */
    public function dispatchHomepage(): void
    {
        $type = $this->app->settings()->get('CMS.homepageType', 'blog');

        if ($type === 'pages') {
            $pageId = $this->app->settings()->get('CMS.homepagePageId');
            if ($pageId !== null) {
                try {
                    $stmt = $this->app->db()->prepare(
                        "SELECT slug FROM pages WHERE id = :id AND status = 'published' AND deleted_at IS NULL LIMIT 1"
                    );
                    $stmt->execute([':id' => $pageId]);
                    $slug = $stmt->fetchColumn();
                    if ($slug !== false) {
                        (new PagesPublicController($this->app))->view((string) $slug, true);
                        return;
                    }
                } catch (\Throwable $e) {
                    // pages table missing or query failed — fall through
                }
            }
        }

        (new BlogPublicController($this->app))->index();
    }

    /**
     * Whether migration and seed checks run on this request.
     *
     * They only run where they can do work:
     *   - fresh install (no .migrations_installed marker): always, because
     *     the session/shield tables must exist before any page loads
     *   - CLI: only for runway (RUNWAY_PROJECT_ROOT defined); cron skips
     *   - web: admin requests only; public pages skip entirely
     */
    protected function shouldRunMigrationsOnThisRequest(): bool
    {
        if (!is_file(PROJECT_ROOT . DIRECTORY_SEPARATOR . '.migrations_installed')) {
            return true;
        }

        if (PHP_SAPI === 'cli') {
            return defined('RUNWAY_PROJECT_ROOT');
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') . '/';

        return str_starts_with($path, '/admin/');
    }

    /**
     * Run migrations + seeds for FOUNDATION packages, unconditional.
     *
     * Foundation packages (enlivenapp/* type flightphp-foundation, pubvana/*
     * type pubvana-foundation) are the required vendor stack. They run BEFORE
     * core so the tables core seeds depend on (e.g. Shield's auth_permissions)
     * exist first. Never gated by plugin_state — they are required by definition.
     *
     * @param array<string, array<string, mixed>> $foundation Discovered foundation packages keyed by plugin ID
     */
    protected function runFoundationMigrations(array $foundation): void
    {
        if ($foundation === []) {
            return;
        }
        if (!class_exists(\Enlivenapp\Migrations\Services\MigrationSetup::class)) {
            return;
        }

        $paths = [];
        $seeds = [];
        $versions = [];

        foreach ($foundation as $pluginId => $info) {
            [$pluginPaths, $pluginSeeds] = $this->pluginMigrationPatterns($pluginId, $info);
            $paths = array_merge($paths, $pluginPaths);
            $seeds = array_merge($seeds, $pluginSeeds);

            $semver = $this->pluginSemver($pluginId, $info);
            if ($semver !== null) {
                $moduleName = ($info['source'] ?? null) === 'vendor'
                    ? $pluginId
                    : 'plugins/' . ($info['folder'] ?? '');
                $versions[$moduleName] = $semver;
            }
        }

        if ($paths === []) {
            return;
        }

        try {
            $migrate = new \Enlivenapp\Migrations\Services\MigrationSetup($this->app->db(), [
                'migrations' => [
                    'paths'    => $paths,
                    'seeds'    => $seeds !== [] ? ['paths' => $seeds] : [],
                    'versions' => $versions,
                ],
            ]);
            $migrate->runMigrate();
        } catch (\Throwable $e) {
            error_log("Foundation migration error: " . $e->getMessage());
        }
    }

    /**
     * Run core-only database migrations.
     *
     * Runs ONLY app/Database/Migrations (and core seeds) so the plugin_state,
     * settings, themes, and navigation tables exist before discovery reads
     * plugin state. Plugin migrations run separately, gated to enabled
     * plugins, after state is resolved.
     */
    protected function runCoreMigrations(): void
    {
        if (!class_exists(\Enlivenapp\Migrations\Services\MigrationSetup::class)) {
            return;
        }
        try {
            $versions = [];
            $coreName = $this->coreName();
            $coreSemver = $this->coreSemver();
            if ($coreName !== null && $coreSemver !== null) {
                $versions[$coreName] = $coreSemver;
            }
            $migrate = new \Enlivenapp\Migrations\Services\MigrationSetup($this->app->db(), [
                'migrations' => [
                    'paths'       => ['app/Database/Migrations'],
                    'seeds'       => ['paths' => []],
                    'versions'    => $versions,
                    'module_names' => $coreName !== null
                        ? ['app/Database/Migrations' => $coreName]
                        : [],
                ],
            ]);
            $migrate->runMigrate();
        } catch (\Throwable $e) {
            error_log("Migration error: " . $e->getMessage());
        }
    }

    /**
     * Run migrations + seeds for ENABLED plugins only.
     *
     * Builds the migration/seed path list from plugin_state instead of blanket
     * globs, so a disabled plugin's directory is never scanned and its
     * migration/seed files are never loaded — that is the enable/disable pause.
     * Patterns keep the same module names ('plugins/Blog', 'author/package')
     * the migrations package recorded historically, so previously-applied
     * migrations stay applied.
     *
     * @param array<string, array<string, mixed>> $all Discovered plugins keyed by plugin ID (see loadPlugins())
     */
    protected function runPluginMigrations(array $all): void
    {
        $paths = [];
        $seeds = [];
        $versions = [];

        foreach ($all as $pluginId => $info) {
            if (!$this->isEnabled($pluginId)) {
                continue;
            }
            // Foundation packages already ran in their own tier.
            if ($this->isFoundationPackage($pluginId, $info)) {
                continue;
            }

            [$pluginPaths, $pluginSeeds] = $this->pluginMigrationPatterns($pluginId, $info);
            $paths = array_merge($paths, $pluginPaths);
            $seeds = array_merge($seeds, $pluginSeeds);

            $semver = $this->pluginSemver($pluginId, $info);
            if ($semver !== null) {
                // Match the module name the migrations package derives from the
                // pattern: plugins/{Folder} for local, {author}/{pkg} for vendor.
                $moduleName = ($info['source'] ?? null) === 'vendor'
                    ? $pluginId
                    : 'plugins/' . ($info['folder'] ?? '');
                $versions[$moduleName] = $semver;
            }
        }

        if ($paths === []) {
            return;
        }

        try {
            $migrate = new \Enlivenapp\Migrations\Services\MigrationSetup($this->app->db(), [
                'migrations' => [
                    'paths'    => $paths,
                    'seeds'    => $seeds !== [] ? ['paths' => $seeds] : [],
                    'versions' => $versions,
                ],
            ]);
            $migrate->runMigrate();
        } catch (\Throwable $e) {
            error_log("Plugin migration error: " . $e->getMessage());
        }
    }

    /**
     * Resolve the module-name-preserving migration/seed patterns for one plugin.
     *
     * Relative patterns are required: the migrations package derives module
     * names from the 'vendor/' and 'plugins/' prefixes in the pattern
     * ('vendor/enlivenapp/flight-sessions/...' → 'enlivenapp/flight-sessions',
     * 'plugins/Blog/Database/Migrations' → 'plugins/Blog'). Keeping these
     * names stable means already-applied migrations are never re-run.
     *
     * @param string               $pluginId Plugin/package ID
     * @param array<string, mixed> $info     Plugin info array (source, folder)
     * @return array{0: string[], 1: string[]} [migration patterns, seed patterns]
     */
    public function pluginMigrationPatterns(string $pluginId, array $info): array
    {
        $paths = [];
        $seeds = [];

        if (($info['source'] ?? null) === 'vendor') {
            foreach (['/src/Database/Migrations', '/Database/Migrations'] as $suffix) {
                $pattern = 'vendor/' . $pluginId . $suffix;
                if (is_dir(PROJECT_ROOT . DIRECTORY_SEPARATOR . $pattern)) {
                    $paths[] = $pattern;
                    break;
                }
            }
            foreach (['/src/Database/Seeds', '/Database/Seeds'] as $suffix) {
                $pattern = 'vendor/' . $pluginId . $suffix;
                if (is_dir(PROJECT_ROOT . DIRECTORY_SEPARATOR . $pattern)) {
                    $seeds[] = $pattern;
                    break;
                }
            }
            return [$paths, $seeds];
        }

        $base = 'plugins/' . ($info['folder'] ?? '');
        if (is_dir(PROJECT_ROOT . DIRECTORY_SEPARATOR . $base . '/Database/Migrations')) {
            $paths[] = $base . '/Database/Migrations';
        }
        if (is_dir(PROJECT_ROOT . DIRECTORY_SEPARATOR . $base . '/Database/Seeds')) {
            $seeds[] = $base . '/Database/Seeds';
        }

        return [$paths, $seeds];
    }

    /**
     * Resolve a plugin's seed version (semver) from its pubvana.json manifest.
     *
     * Local plugins carry semver in their pubvana.json. Vendor packages may
     * also carry one; when absent, the migrations package falls back to the
     * composer installed.json version.
     *
     * @param string               $pluginId Plugin/package ID
     * @param array<string, mixed> $info     Plugin info array (source, folder, manifest)
     * @return string|null Semver string, or null when none is declared
     */
    public function pluginSemver(string $pluginId, array $info): ?string
    {
        if (($info['source'] ?? null) === 'local') {
            $semver = $info['manifest']['semver'] ?? null;
            return is_string($semver) && $semver !== '' ? $semver : null;
        }

        $manifestFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'vendor'
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pluginId)
            . DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($manifestFile)) {
            return null;
        }
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $semver = is_array($manifest) ? ($manifest['semver'] ?? null) : null;
        return is_string($semver) && $semver !== '' ? $semver : null;
    }

    /**
     * The core package identity (name) from the root pubvana.json.
     *
     * Core is not a plugin, so the migrations package would otherwise derive a
     * basename artifact ('Migrations') for its module name. Giving it the real
     * package identity keeps the seeds/migrations tables readable: rows read
     * 'pubvana/pubvana', not 'Migrations'.
     *
     * @return string|null Package name, or null when root pubvana.json is absent
     */
    protected function coreName(): ?string
    {
        $manifestFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($manifestFile)) {
            return null;
        }
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $name = is_array($manifest) ? ($manifest['name'] ?? null) : null;
        return is_string($name) && $name !== '' ? $name : null;
    }

    /**
     * The seed version for core (app/Database) from the root pubvana.json.
     *
     * @return string|null Semver string, or null when root pubvana.json is absent
     */
    protected function coreSemver(): ?string
    {
        $manifestFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($manifestFile)) {
            return null;
        }
        $manifest = json_decode((string) file_get_contents($manifestFile), true);
        $semver = is_array($manifest) ? ($manifest['semver'] ?? null) : null;
        return is_string($semver) && $semver !== '' ? $semver : null;
    }

    /**
     * Ensure a plugin_state row exists for every discovered plugin and cache
     * the resolved state for this request.
     *
     * Newly discovered plugins get first-discovery defaults (see defaultState()):
     * required core plugins and shipped local plugins are enabled; everything
     * else stays disabled until an admin enables it. Existing rows are never
     * modified here — state only changes through the admin Plugins page.
     *
     * @param array<string, array<string, mixed>> $all Discovered plugins keyed by plugin ID (priorities overwritten in place)
     */
    protected function syncPluginStates(array &$all): void
    {
        try {
            // One bulk fetch for every existing plugin_state row, keyed by plugin_id.
            // Replaces a per-plugin findByPluginId() round-trip (10 plugins = 10 queries
            // down to 1). Newly discovered plugins are inserted below; existing rows are
            // never modified here — state only changes through the admin Plugins page.
            $existing = (new \Pubvana\Models\PluginState($this->app->db()))->getAllByPluginId();

            foreach ($all as $pluginId => $info) {
                $state = $existing[$pluginId] ?? null;

                if ($state === null) {
                    $defaults = $this->defaultState($pluginId, $info);
                    $now = date('Y-m-d H:i:s');

                    $state = new \Pubvana\Models\PluginState($this->app->db());
                    $state->plugin_id = $pluginId;
                    $state->enabled = $defaults['enabled'];
                    $state->priority = $defaults['priority'];
                    $state->required = $defaults['required'];
                    $state->created_at = $now;
                    $state->updated_at = $now;
                    $state->insert();
                }

                $this->pluginStates[$pluginId] = [
                    'enabled'  => (bool) $state->enabled,
                    'priority' => (int) $state->priority,
                    'required' => (bool) $state->required,
                ];
                // Sorting and migration gating use the DB-backed priority.
                $all[$pluginId]['priority'] = (int) $state->priority;
            }
        } catch (\Throwable $e) {
            error_log('PluginLoader: plugin state sync failed - ' . $e->getMessage());
        }
    }

    /**
     * First-discovery defaults for a plugin with no plugin_state row yet.
     *
     * 1. Required core plugins (sessions/shield/csrf) are enabled + locked.
     * 2. An explicit enabled/priority entry in the app-passed plugin config is inherited.
     * 3. Local plugins ship with Pubvana, so they are enabled by default.
     * 4. Anything else starts DISABLED — the pause. Its code runs nothing
     *    until an admin enables it on the Plugins page.
     *
     * @param string               $pluginId Plugin/package ID
     * @param array<string, mixed> $info     Plugin info array (source)
     * @return array{enabled: bool, priority: int, required: bool}
     */
    protected function defaultState(string $pluginId, array $info): array
    {
        $config = $this->enabledPlugins[$pluginId] ?? [];

        $state = [
            'enabled'  => !empty($config['enabled']),
            'priority' => (int) ($config['priority'] ?? 50),
            'required' => false,
        ];

        if ($this->isFoundationPackage($pluginId, $info)) {
            $state['enabled']  = true;
            $state['priority'] = $this->requiredPlugins[$pluginId] ?? (int) ($config['priority'] ?? 50);
            $state['required'] = true;
        } elseif (!isset($config['enabled'])) {
            // Never hand-configured: local plugins ship with Pubvana, vendor
            // packages pause disabled until an admin enables them.
            $state['enabled'] = ($info['source'] ?? null) === 'local';
        }

        return $state;
    }

    /**
     * Build the enabled-gated migration/seed path list (core + enabled plugins).
     *
     * Passed to CLI tooling as an app value so disabled plugin
     * migrations never run from the command line either.
     *
     * @return array{paths: string[], seeds: array{paths: string[]}}
     */
    public function getMigrationConfig(): array
    {
        // Tier order: foundation first, then core, then enabled non-foundation
        // plugins. Core seed dir is derived from its migration dir by the
        // migrations package (resolveModuleSeedDir), so it is NOT listed in
        // seeds.paths — listing it would create a phantom "Seeds" module.
        $foundationPaths = [];
        $foundationSeeds = [];
        $paths = ['app/Database/Migrations'];
        $seeds = [];
        $versions = [];
        $moduleNames = [];

        $coreName = $this->coreName();
        $coreSemver = $this->coreSemver();
        if ($coreName !== null && $coreSemver !== null) {
            $versions[$coreName] = $coreSemver;
            $moduleNames['app/Database/Migrations'] = $coreName;
        }

        $all = array_merge($this->discoverLocal(), $this->discoverVendor());
        foreach ($all as $pluginId => $info) {
            if ($this->isFoundationPackage($pluginId, $info)) {
                [$fp, $fs] = $this->pluginMigrationPatterns($pluginId, $info);
                $foundationPaths = array_merge($foundationPaths, $fp);
                $foundationSeeds = array_merge($foundationSeeds, $fs);
                $semver = $this->pluginSemver($pluginId, $info);
                if ($semver !== null) {
                    $moduleName = ($info['source'] ?? null) === 'vendor'
                        ? $pluginId
                        : 'plugins/' . ($info['folder'] ?? '');
                    $versions[$moduleName] = $semver;
                }
                continue;
            }

            if (!$this->isEnabled($pluginId)) {
                continue;
            }
            [$pluginPaths, $pluginSeeds] = $this->pluginMigrationPatterns($pluginId, $info);
            $paths = array_merge($paths, $pluginPaths);
            $seeds = array_merge($seeds, $pluginSeeds);

            $semver = $this->pluginSemver($pluginId, $info);
            if ($semver !== null) {
                $moduleName = ($info['source'] ?? null) === 'vendor'
                    ? $pluginId
                    : 'plugins/' . ($info['folder'] ?? '');
                $versions[$moduleName] = $semver;
            }
        }

        return [
            'paths'       => array_merge($foundationPaths, $paths),
            'seeds'       => ['paths' => array_merge($foundationSeeds, $seeds)],
            'versions'    => $versions,
            'module_names' => $moduleNames,
        ];
    }
}
