<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * ExtensionRegistry - Central registry for all plugin extensions.
 *
 * Originally called "AdminExtend" in flight-school (hence the $app->adext()
 * accessor), this handles everything: UI metadata (menu items, nav items,
 * dashboard cards, CSS, JS), route definitions, and block registration.
 *
 * One registration, two consumers: templates read from adext, Flight's
 * router reads routes from adext. This is the one-stop-shop.
 *
 * Types use dot notation for scope clarity:
 *   - admin.menu, admin.dashboard, admin.css, admin.js  (admin area)
 *   - public.nav, public.css, public.js                 (public site)
 *   - block                                             (region system)
 *
 * This class is schema-validated. Every type, slot, and required key is
 * defined in TYPES. If a plugin registers something that doesn't match
 * the schema, it gets rejected with a clear error message. No silent failures.
 *
 * Usage from a plugin:
 *   $app->adext()->register('admin.menu', 'content', 'pubvana.blog', [
 *       'label'    => 'Blog',
 *       'icon'     => 'ti-pencil',
 *       'url'      => '/blog',
 *       'priority' => 20,
 *       'route'    => ['GET', '/blog', [BlogController::class, 'index']],
 *   ]);
 *
 * Usage from a template:
 *   $menuItems = $app->adext()->get('admin.menu', 'content');
 *   foreach ($menuItems as $item) { ... }
 *
 * @package Pubvana\Services
 */
class ExtensionRegistry
{
    /**
     * Allowed extension types, their valid slots, and required/optional keys.
     *
     * To add a new extension type:
     *   1. Add an entry here with slots, required keys, and optional keys
     *   2. Consume it in a template or controller with $app->adext()->get()
     *
     * @var array<string, array{slots: string[], required: string[], optional: string[]}>
     */
    public const TYPES = [
        /*
        |------------------------------------------------------------------
        | Admin Types
        |------------------------------------------------------------------
        | These are for the admin area. URLs and route paths auto-get /admin prefix.
        */
        'admin.menu' => [
            'slots'    => ['content', 'appearance', 'tools', 'settings', 'plugins'],
            'required' => ['label', 'url'],
            'optional' => ['icon', 'priority', 'submenu', 'route', 'middleware', 'core'],
        ],
        'admin.dashboard' => [
            'slots'    => ['cards', 'sections'],
            'required' => ['label'],
            'optional' => ['callable', 'output', 'value', 'icon', 'color', 'priority'],
        ],
        'admin.settings' => [
            // Settings pages. Each contribution becomes a tab on the
            // settings page (slot 'general', the tabbed page at
            // /admin/settings) or a standalone page owned by its
            // controller (slot 'email', the Tools > Email page). Its
            // fields[] entries declare the settings it owns (key must be
            // namespaced dot notation). Declared keys are the ONLY keys
            // savable through the settings UI - undeclared keys
            // (deployment values) can never enter the settings store.
            // Valid field types: text, email, number, textarea, select,
            // checkbox, password (SettingsService::FIELD_TYPES). Optional
            // per-field keys: default, options (select), fallback
            // (app-store key), autoload (bool, default true),
            // description.
            'slots'    => ['general', 'email'],
            'required' => ['label', 'fields'],
            'optional' => ['description', 'priority'],
        ],
        'admin.css' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'admin.js' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Public Types
        |------------------------------------------------------------------
        | These are for the public site. No URL prefix applied.
        */
        'public.nav' => [
            'slots'    => ['main', 'footer', 'sidebar'],
            'required' => ['label', 'url'],
            'optional' => ['icon', 'priority', 'submenu', 'route', 'middleware'],
        ],
        'public.css' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'public.js' => [
            'slots'    => ['default'],
            'required' => ['url'],
            'optional' => ['priority'],
        ],
        'public.head' => [
            'slots'    => ['other'],
            'required' => ['output'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Navigation Linkable Type
        |------------------------------------------------------------------
        | Plugins register items for the Quick Add dropdown in the
        | navigation admin. The callable returns an array of
        | ['label' => '...', 'url' => '...'] entries.
        */
        'nav.linkable' => [
            'slots'    => ['default'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Block Type (Region System)
        |------------------------------------------------------------------
        | Plugins register blocks that can be placed in theme regions.
        */
        'block' => [
            'slots'    => ['available'],
            'required' => ['label', 'template'],
            'optional' => ['description', 'provider', 'priority', 'options'],
        ],

        /*
        |------------------------------------------------------------------
        | Search Type
        |------------------------------------------------------------------
        | Plugins register their content as searchable. The Search plugin
        | lists registered sources in the admin (to enable/disable them)
        | and invokes each provider's callable with a raw query string.
        | The provider returns normalized content matches; SearchService owns
        | all ranking/scoring.
        */
        'search' => [
            'slots'    => ['provider'],
            'required' => ['label', 'callable'],
            'optional' => ['description', 'priority', 'content_type'],
        ],

        /*
        |------------------------------------------------------------------
        | Comments Host Type
        |------------------------------------------------------------------
        | Content plugins register themselves as comment hosts so the Comments
        | plugin can associate stored comments with their content and enrich
        | displays (recent-comments block). The callable enumerates the host's
        | commentable content items.
        |
        | Each enumerated item:
        |   type            - the commentable_type token that matches comments
        |                     stored against this plugin (e.g. 'blog')
        |   id              - the content record id
        |   title/label     - human-readable title
        |   url             - public URL to the item
        |   allow_comments  - (bool) whether comments are open on this item
        |
        | The Comments service reads these hosts to resolve a comment's target
        | content. Rendering a comment thread for a specific item is done by the
        | host's own controller via CommentService::render() / dataFor().
        */
        'comments.host' => [
            'slots'    => ['content'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Content Render Type
        |------------------------------------------------------------------
        | Plugins register content-transform callables that run on rich
        | text bodies (pages, posts) before they reach the template. Each
        | callable receives ['content' => string] and returns the modified
        | string. ContentService::render() chains them in priority order.
        */
        'content.render' => [
            'slots'    => ['default'],
            'required' => ['callable'],
            'optional' => ['label', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Content Edit Panel Type
        |------------------------------------------------------------------
        | Plugins contribute panels rendered inside other plugins' content
        | edit forms (e.g. the SEO panel in the post/page editor). The
        | callable receives ['content_type' => ..., 'content_id' => ...]
        | as context and returns an HTML string to embed.
        */
        'content.edit.panel' => [
            'slots'    => ['default'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Health Check Type
        |------------------------------------------------------------------
        | Site Health extension point. Plugins register additional health
        | checks here. Each contribution's callable is invoked with no
        | arguments and must return a CheckResult (or an array with an 'id').
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('health', 'checks', 'vendor.plugin-name', [
        |       'priority' => 50,
        |       'callable' => fn() => (new YourCustomCheck())->run(),
        |   ]);
        |
        | The HealthService reads them via $app->adext()->get('health', 'checks')
        | and merges them into the check results.
        */
        'health' => [
            'slots'    => ['checks'],
            'required' => ['callable'],
            'optional' => ['label', 'priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Broken Links Source Type
        |------------------------------------------------------------------
        | Content plugins register their content as scan sources for the
        | Broken Links plugin. Each contribution's callable returns an
        | array of content items to scan:
        |   type      - the source type token (e.g. 'post', 'page')
        |   id        - the content record id
        |   title     - human-readable title
        |   content   - the content body to scan for outbound links
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('brokenlinks', 'source', 'pubvana.myplugin', [
        |       'label'    => 'My Plugin Items',
        |       'callable' => fn() => [[
        |           'type'    => 'item',
        |           'id'      => 1,
        |           'title'   => 'Item title',
        |           'content' => '<a href="https://example.com">Example</a>',
        |       ]],
        |   ]);
        |
        | The BrokenLinksService reads them via $app->adext()->get('brokenlinks', 'source').
        */
        'brokenlinks' => [
            'slots'    => ['source'],
            'required' => ['label', 'callable'],
            'optional' => ['priority'],
        ],

        /*
        |------------------------------------------------------------------
        | Cron Task Type
        |------------------------------------------------------------------
        | Plugins register callables that run on a fixed interval. There are
        | three slots, each driven by a system crontab line calling the root
        | `cron` script: 1m (every minute), 4h, and 24h. No web routes are
        | involved; the script is CLI-only and not web-exposed.
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $app->adext()->register('cron', '1m', 'pubvana.blog', [
        |       'label'    => 'Ping feeds',
        |       'callable' => fn() => $this->pingFeeds(),
        |   ]);
        |
        | CronService reads these via $app->adext()->get('cron', $interval)
        | and runs each callable in priority order. One failing task never
        | blocks the rest.
        */
        'cron' => [
            'slots'    => ['1m', '4h', '24h'],
            'required' => ['callable'],
            'optional' => ['label', 'priority', 'run_result'],
        ],

        /*
        |------------------------------------------------------------------
        | CSRF Exempt Type
        |------------------------------------------------------------------
        | Plugins register URL path prefixes whose state-changing requests
        | skip CSRF validation, e.g. sessionless webhook and API endpoints
        | that authenticate with per-request gateway signatures or bearer
        | keys and cannot present a session token. The core CSRF gate in
        | app/config/services.php reads these prefixes from the registry
        | instead of a hardcoded path list.
        |
        | Registration (from a plugin's Plugin.php register()):
        |   $adext->register('csrf.exempt', 'default', 'pubvana.mystore', [
        |       'prefix' => '/store/webhook/',
        |       'label'  => 'Digital Store webhooks',
        |   ]);
        |
        | The prefix must be a path prefix ending in '/' unless the
        | exemption targets one exact path. Admin child pages must not be
        | registered here; they start with /admin and stay protected.
        */
        'csrf.exempt' => [
            'slots'    => ['default'],
            'required' => ['prefix'],
            'optional' => ['label', 'description', 'priority'],
        ],
    ];

    /**
     * All registered extensions, keyed by type, then slot, then contributor key.
     *
     * @var array<string, array<string, array<string, array<string, mixed>>>>
     */
    protected array $extensions = [];

    /**
     * Route definitions collected from registrations.
     *
     * Each entry: [method, path, handler, middleware, scope, source, isCore]
     * scope is 'admin' or 'public' - determines /admin prefix.
     * source is the registering key (e.g. 'pubvana.users', 'pubvana.blog').
     * isCore marks routes that cannot be overridden by plugins.
     *
     * @var array<int, array{method: string, path: string, handler: callable|array{0: class-string, 1: string}, middleware: array<int, mixed>, scope: string, source: string, isCore: bool}>
     */
    protected array $routes = [];

    /**
     * Set of route signatures that are core (cannot be overridden).
     *
     * Format: 'METHOD /path' (after /admin prefix applied)
     *
     * @var array<string, string> Signature => source key
     */
    protected array $coreRouteSignatures = [];

    /**
     * Register one or more contributions to a typed extension point.
     *
     * Two calling conventions:
     *
     *   // Single item (original)
     *   $adext->register('admin.menu', 'settings', 'pubvana.users', ['label' => 'Users', ...]);
     *
     *   // Batch: $key is an array of key => config pairs, $config omitted
     *   $adext->register('admin.menu', 'settings', [
     *       'pubvana.users'        => ['label' => 'Users', ...],
     *       'pubvana.groups'       => ['label' => 'Groups', ...],
     *   ]);
     *
     * Validates against the TYPES schema. Rejects unknown types, unknown slots,
     * missing required keys, unknown keys, and duplicate contributor keys.
     * Logs clear errors so plugin authors know exactly what they broke.
     *
     * If a 'route' key is provided (for menu/nav types), the route is
     * automatically stored for later registration with Flight's router.
     *
     * URL auto-prefixing:
     *   - admin.* types: auto-prefixes /admin
     *   - public.* types: no prefix
     *
     * @param string               $type   The extension type (must exist in TYPES)
     * @param string               $slot   The slot name (must exist in TYPES[$type]['slots'])
     * @param string|array<string, mixed> $key    Single key string, or array of key => config pairs
     * @param array<string, mixed> $config Data being registered (single mode only)
     *
     * @return void
     */
    public function register(string $type, string $slot, string|array $key, array $config = []): void
    {
        if (!isset(self::TYPES[$type])) {
            $allowed = implode(', ', array_keys(self::TYPES));
            error_log("ExtensionRegistry: unknown type '{$type}'. Allowed: {$allowed}");
            return;
        }

        $schema = self::TYPES[$type];

        if (!in_array($slot, $schema['slots'], true)) {
            $allowed = implode(', ', $schema['slots']);
            error_log("ExtensionRegistry: unknown slot '{$slot}' for type '{$type}'. Allowed: {$allowed}");
            return;
        }

        // Batch mode: $key is [contributorKey => config, ...]
        if (is_array($key)) {
            foreach ($key as $batchKey => $batchConfig) {
                $this->register($type, $slot, $batchKey, $batchConfig);
            }
            return;
        }

        // Single mode
        $missing = array_diff($schema['required'], array_keys($config));
        if (!empty($missing)) {
            $keys = implode(', ', $missing);
            error_log("ExtensionRegistry: missing required keys [{$keys}] for '{$type}.{$slot}' (key: '{$key}')");
            return;
        }

        $allowedKeys = array_merge($schema['required'], $schema['optional']);
        $unknown = array_diff(array_keys($config), $allowedKeys);
        if (!empty($unknown)) {
            $keys = implode(', ', $unknown);
            error_log("ExtensionRegistry: unknown keys [{$keys}] for '{$type}.{$slot}' (key: '{$key}'). Allowed: " . implode(', ', $allowedKeys));
            return;
        }

        if (isset($this->extensions[$type][$slot][$key])) {
            error_log("ExtensionRegistry: duplicate key '{$key}' in [{$type}][{$slot}] - rejected.");
            return;
        }

        // Auto-prefix /admin URLs for admin types
        if (str_starts_with($type, 'admin.')) {
            if (isset($config['url'])) {
                $config['url'] = '/admin/' . ltrim($config['url'], '/');
            }
            if (!empty($config['submenu'])) {
                foreach ($config['submenu'] as $subKey => $sub) {
                    if (isset($sub['url'])) {
                        $config['submenu'][$subKey]['url'] = '/admin/' . ltrim($sub['url'], '/');
                    }
                }
            }
        }

        // Transform plugin asset URLs for CSS/JS types
        // /plugins/{Plugin}/assets/{path} → /assets/plugin/{Plugin}/{path}
        if (in_array($type, ['admin.css', 'admin.js', 'public.css', 'public.js'], true)) {
            if (isset($config['url']) && preg_match('#^/plugins/([^/]+)/assets/(.+)$#', $config['url'], $matches)) {
                $pluginName = $matches[1];
                $assetPath = $matches[2];
                $config['url'] = '/assets/plugin/' . $pluginName . '/' . $assetPath;
            }
        }

        // Collect route if provided
        if (isset($config['route']) && is_array($config['route'])) {
            $scope = str_starts_with($type, 'admin.') ? 'admin' : 'public';
            $this->addRoute(
                $config['route'][0] ?? 'GET',
                $config['route'][1] ?? $config['url'],
                $config['route'][2] ?? null,
                $config['middleware'] ?? [],
                $scope,
                $key
            );
        }

        $this->extensions[$type][$slot][$key] = $config;
    }

    /**
     * Get all contributions for a typed extension slot, sorted by priority.
     *
     * When context is provided and a contribution has a 'callable' key,
     * the callable is invoked with the context and its return value is
     * merged into the contribution.
     *
     * @param string                                 $type    The extension type (e.g. 'admin.menu', 'public.nav')
     * @param string                                 $slot    The slot name (e.g. 'content', 'main')
     * @param array<string, mixed>                   $context Optional context passed to callable contributors
     *
     * @return array<string, array<string, mixed>> Contributions sorted by priority
     */
    public function get(string $type, string $slot, array $context = []): array
    {
        $items = $this->extensions[$type][$slot] ?? [];

        if (!empty($context)) {
            foreach ($items as $key => &$item) {
                if (isset($item['callable']) && is_callable($item['callable'])) {
                    $result = call_user_func($item['callable'], $context);
                    if (is_array($result)) {
                        $item = array_merge($item, $result);
                    } elseif (is_string($result)) {
                        $item['output'] = $result;
                    }
                }
            }
            unset($item);
        }

        uasort($items, fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        foreach ($items as &$item) {
            if (!empty($item['submenu']) && is_array($item['submenu'])) {
                uasort($item['submenu'], fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Check if any contributions exist for a typed extension slot.
     *
     * @param string $type The extension type
     * @param string $slot The slot name
     *
     * @return bool
     */
    public function has(string $type, string $slot): bool
    {
        return !empty($this->extensions[$type][$slot]);
    }

    /**
     * Store a single route definition for later registration.
     *
     * @param string         $method     HTTP method (GET, POST, etc.)
     * @param string                    $path       URL path (e.g. '/blog', '/users/@id/edit')
     * @param callable|array{0: class-string, 1: string} $handler    Route handler (closure, [Controller::class, 'method'], etc.)
     * @param array<int, mixed>         $middleware  Middleware instances to apply
     * @param string                    $scope      'admin' or 'public' - determines /admin prefix
     * @param string                    $source     Registering key (e.g. 'pubvana.users', 'pubvana.blog')
     * @param bool                      $isCore     Whether this is a core route (cannot be overridden)
     *
     * @return int Number of routes successfully added (0 or 1)
     */
    public function addRoute(string $method, string $path, callable|array $handler, array $middleware = [], string $scope = 'public', string $source = 'unknown', bool $isCore = false): int
    {
        return $this->addRoutes($scope, [[$method, $path, $handler, $middleware]], $source, $isCore);
    }

    /**
     * Store multiple route definitions for later registration.
     *
     * Processes all routes in a single call, applying the same source
     * and isCore flags to all routes in the batch.
     *
     * Each route in $routes is an array: [method, path, handler, middleware?]
     * middleware is optional and defaults to [].
     *
     * Conflict resolution:
     *   - Core routes (isCore=true) always win over plugin routes
     *   - First registered route wins on conflict
     *   - Rejected routes are logged with the conflicting source
     *
     * @param string               $scope   'admin' or 'public' - determines /admin prefix
     * @param array<int, array<int|string, mixed>> $routes  Array of route definitions: [method, path, handler, middleware?]
     * @param string               $source  Registering key (e.g. 'pubvana.core', 'pubvana.blog')
     * @param bool                 $isCore  Whether these are core routes (cannot be overridden)
     *
     * @return int Number of routes successfully added
     */
    public function addRoutes(string $scope, array $routes, string $source = 'unknown', bool $isCore = false): int
    {
        $added = 0;

        foreach ($routes as $route) {
            $method = strtoupper($route[0]);
            $path = $route[1];
            $handler = $route[2];
            $middleware = $route[3] ?? [];

            // Build the signature with /admin prefix applied
            $fullPath = $scope === 'admin'
                ? '/admin/' . ltrim($path, '/')
                : $path;
            $signature = $method . ' ' . $fullPath;

            // Check for conflict with core routes
            if (isset($this->coreRouteSignatures[$signature])) {
                error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (core route from '{$this->coreRouteSignatures[$signature]}' cannot be overridden)");
                continue;
            }

            // Check for conflict with existing routes
            $conflict = false;
            foreach ($this->routes as $existing) {
                $existingSig = strtoupper($existing['method']) . ' ' . (
                    $existing['scope'] === 'admin'
                        ? '/admin/' . ltrim($existing['path'], '/')
                        : $existing['path']
                );

                if ($existingSig === $signature) {
                    if ($existing['isCore']) {
                        error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (core route from '{$existing['source']}' cannot be overridden)");
                    } else {
                        error_log("ExtensionRegistry: route conflict - '{$source}' rejected '{$signature}' (first registered by '{$existing['source']}' wins)");
                    }
                    $conflict = true;
                    break;
                }
            }

            if ($conflict) {
                continue;
            }

            // No conflict - add the route
            $this->routes[] = [
                'method'     => $method,
                'path'       => $path,
                'handler'    => $handler,
                'middleware'  => $middleware,
                'scope'      => $scope,
                'source'     => $source,
                'isCore'     => $isCore,
            ];

            // Track core route signatures
            if ($isCore) {
                $this->coreRouteSignatures[$signature] = $source;
            }

            $added++;
        }

        return $added;
    }

    /**
     * Register all collected routes with Flight's router.
     *
     * Call this after all plugins have loaded. For admin routes, the /admin
     * prefix is automatically prepended to the path.
     *
     * @param Engine<object> $app The FlightPHP app instance
     *
     * @return void
     */
    public function registerRoutes(Engine $app): void
    {
        $router = $app->router();

        foreach ($this->routes as $route) {
            $path = $route['scope'] === 'admin'
                ? '/admin/' . ltrim($route['path'], '/')
                : $route['path'];

            $fullPath = $route['method'] . ' ' . $path;
            $flightRoute = $router->map($fullPath, $route['handler']);

            // Non-object entries (null placeholders, class strings) are skipped
            // on purpose; only middleware instances are applied here.
            foreach ($route['middleware'] as $mw) {
                if (is_object($mw)) {
                    $flightRoute->addMiddleware($mw);
                }
            }
        }
    }

    /**
     * Get all stored routes.
     *
     * @return array<int, array{method: string, path: string, handler: callable|array{0: class-string, 1: string}, middleware: array<int, mixed>, scope: string, source: string, isCore: bool}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
