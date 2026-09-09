<?php

/**
 * Services: registers all core services with FlightPHP.
 *
 * This file wires up everything the application needs:
 *   1. Tracy debugger (dev only)
 *   2. Database connection (PDO)
 *   3. Session management
 *   4. Authentication (flight-shield)
 *   5. Extension registry ($app->adext())
 *   6. Slug generator
 *   7. Settings store ($app->settings())
 *   8. View system (PluginView with 3-tier template resolution)
 *   9. Plugin loader (discovers and boots all plugins)
 *  10. Global error handler
 *
 * Services are registered in two ways:
 *   - $app->set('name', $value): stores a value, retrieved with $app->get('name')
 *   - $app->map('name', fn() => $x): stores a callable, retrieved with $app->name()
 *
 * @package Pubvana\Config
 */

use flight\Engine;
use flight\database\SimplePdo;
use flight\debug\database\PdoQueryCapture;
use flight\debug\tracy\TracyExtensionLoader;
use Tracy\Debugger;
use Pubvana\Services\PluginView;

$app = $app ?? Flight::app();
$ds = DIRECTORY_SEPARATOR;

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// Global view helpers (guard-guarded, safe to load once per boot). Required
// here so both boot paths (web bootstrap.php and the CLI, which loads this
// file directly) carry them.
require(__DIR__ . $ds . '..' . $ds . 'Support' . $ds . 'helpers.php');

// Ensure config values are loaded into the app. There is no single config
// file anymore, so $config is never populated under either boot path;
// values come from env-overrides.php below.
if ($app->get('database') === null && is_array($config ?? null)) {
    foreach ($config as $key => $value) {
        $app->set($key, $value);
    }
}

// .env overrides + HTTPS policy derivation. Idempotent: under web boot
// bootstrap.php already ran this; under CLI (runway) this is the one shot
// that keeps CLI config identical to web config (DB creds included).
require(__DIR__ . $ds . 'env-overrides.php');

/*
|--------------------------------------------------------------------------
| Default Timezone
|--------------------------------------------------------------------------
| MOVED: applied further down this file, after the settings store is
| mapped. CMS.defaultTimezone is DB-backed (admin-editable), and the
| settings service needs the DB connection - which did not exist at
| this file's top when the timezone was still config-only.
*/

/*
|--------------------------------------------------------------------------
| Tracy Debugger / Error Reporting
|--------------------------------------------------------------------------
| APP_ENV decides who owns error reporting; APP_DEBUG (flight.debug) decides
| how verbose development is:
|
|   development + APP_DEBUG=true  -> Tracy DEVELOPMENT mode: debug bar,
|                                    full trace pages, strict mode
|   development + APP_DEBUG=false -> Tracy PRODUCTION mode: exceptions are
|                                    logged to writable/logs/, generic page
|   production                    -> Flight owns errors: mapped 'error'
|                                    handler logs + renders JSON/HTML below
*/

// All Tracy logging, regardless of environment, lands in writable/logs so it
// stays beside Flight's error-handler logs below.
Debugger::$logDirectory = PROJECT_ROOT . $ds . 'writable' . $ds . 'logs';

if ($app->get('environment') === 'development') {
    // Flight must NOT register its own handlers or they overwrite Tracy's
    // (start() registers later than this file runs).
    $app->set('flight.handle_errors', false);

    if ($app->get('flight.debug') === true) {
        Debugger::enable(Debugger::DEVELOPMENT, Debugger::$logDirectory);
        Debugger::$strictMode = true;

        // Flight emits a Content-Length header by default, which Tracy's debug
        // bar cannot inject around and logs a spurious exception on every page.
        // Drop it in debug mode so the bar renders on HTML responses. Binary
        // assets are unaffected: AssetService::serve() sets its own
        // Content-Length and suppresses the bar for those responses.
        $app->set('flight.content_length', false);
    } else {
        Debugger::enable(Debugger::PRODUCTION, Debugger::$logDirectory);
    }

    // TracyExtensionLoader requires flightphp/tracy-extensions
    if (Debugger::$showBar === true && php_sapi_name() !== 'cli') {
        (new TracyExtensionLoader($app));
    }
}

/*
|--------------------------------------------------------------------------
| Database Connection (PDO)
|--------------------------------------------------------------------------
| Creates a PDO connection to MySQL/MariaDB. In development with Tracy's
| debug bar active, wraps it with PdoQueryCapture for the query panel.
| In production, uses SimplePdo (the successor to the deprecated PdoWrapper).
|
| Access anywhere with: Flight::db() or $app->db()
*/
$db = $app->get('database');
$dsn = "{$db['driver']}:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}";

if ($app->get('environment') === 'development' && Debugger::$showBar === true) {
    $pdo = new PdoQueryCapture($dsn, $db['user'], $db['password']);
} else {
    $pdo = new SimplePdo($dsn, $db['user'], $db['password']);
}

$app->set('db', $pdo);
$app->map('db', fn() => $pdo);

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
| Database-backed sessions with encrypted payloads, provided by the
| enlivenapp/flight-sessions plugin. Its Plugin.php binds $app->session()
| and starts the session (hardened cookies + DB save handler) before
| any consumer. flight-shield auth now runs on this service.
|
| Config: encryption key from SESSION_ENCRYPTION_KEY (.env), folded by
| env-overrides.php under plugins.enlivenapp/flight-sessions
*/

/*
|--------------------------------------------------------------------------
| Authentication (flight-shield)
|--------------------------------------------------------------------------
| Provides login/logout, user management, groups, permissions, and
| password hashing. Ported from CodeIgniter4 Shield.
|
| Auth is registered by Shield's Plugin.php during loadPlugins().
| The 'auth' config (authenticators, session, passwords, etc.) is
| loaded from vendor/enlivenapp/flight-shield/src/Config/Config.php
| by the PluginLoader's vendor config loader.
|
| Access anywhere with: $app->auth()->user(), returning the user entity or null
| Check permissions:    $app->auth()->user()->inGroup('superadmin')
*/

/*
|--------------------------------------------------------------------------
| CSRF Protection (flight-csrf)
|--------------------------------------------------------------------------
| Validates CSRF tokens on state-changing requests (POST, PUT, PATCH, DELETE).
| Tokens are generated by csrf_field() in views and validated here.
|
| NOTE: $csrf->before() is deliberately invoked AFTER loadPlugins() below
| (see the plugin loader section). It must run after the sessions plugin
| has started the DB-backed session so CSRF tokens persist across
| requests in the same store as everything else.
*/
$csrf = new \Enlivenapp\FlightCsrf\Middlewares\CsrfMiddleware($app);

/*
|--------------------------------------------------------------------------
| Migrations & Seeds
|--------------------------------------------------------------------------
| The enabled-gated migration set comes from $pluginLoader->getMigrationConfig()
| (the single source of truth) and is registered below so both web and CLI
| (runway loads this file) resolve it from the Flight store. app/config/
| migrations.php remains only as a no-Flight fallback (core paths).
*/

$app->map('adext', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\ExtensionRegistry();
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Slug Generator
|--------------------------------------------------------------------------
| Converts text to URL-safe slugs. Used by blog, pages, and any content
| type that needs clean URLs.
|
| Usage: $app->slugify('Hello World!') → 'hello-world'
*/
$app->map('slugify', function (string $text): string {
    $text = strtolower(trim($text));
    $text = str_replace('&', 'and', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
});

/*
|--------------------------------------------------------------------------
| View System (PluginView)
|--------------------------------------------------------------------------
| Extends Flight's native View with 3-tier template resolution:
|   1. app/Views/{plugin}/{file}: site owner override (highest priority)
|   2. themes/{active}/Views/{plugin}/{file}: theme override
|   3. plugins/{plugin}/Views/{file}: plugin default (lowest priority)
|
| Admin routes (/admin/*) use native PHP templates (.php files).
| Public routes use Vision templates (.tpl files). No PHP runs in
| public templates for security.
|
| Plugins register their view directories with:
|   $view->addPluginPath('pubvana/blog', '/path/to/plugins/blog/Views')
|
| The PluginViewContextMiddleware sets the active plugin before each
| controller runs, so un-prefixed render('login') calls resolve to
| the correct plugin's views automatically.
*/
$appViewPath = PROJECT_ROOT . $ds . 'app' . $ds . 'Views';
$pluginView = new PluginView($appViewPath);
$pluginView->extension = '.php';

// Set the active theme's Views/ directory for the theme override tier
$themeName = $app->get('active_theme') ?? 'default';
$themePath = PROJECT_ROOT . $ds . 'themes' . $ds . $themeName . $ds . 'Views';
if (is_dir($themePath)) {
    $pluginView->setThemePath($themePath);
}

$app->register('view', PluginView::class, [$appViewPath], function (PluginView $view) use ($pluginView) {
    $view->extension = $pluginView->extension;
    $view->setThemePath($pluginView->getThemePath());
});
$app->set('view', $pluginView);

/*
|--------------------------------------------------------------------------
| Theme Service
|--------------------------------------------------------------------------
| Discovers, validates, activates, and manages themes. Provides theme
| options and asset publishing.
|
| Access anywhere with: $app->themes()
*/
$app->map('themes', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\ThemeService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Region Manager
|--------------------------------------------------------------------------
| Manages content regions and block placements for the public theme system.
| Regions are named areas in a page layout (header, sidebar, footer, etc.).
| Blocks are content blocks placed into regions via the admin UI.
|
| Access anywhere with: $app->regions()
*/
$app->map('regions', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\RegionManager($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Navigation Service
|--------------------------------------------------------------------------
| Manages navigation menus: CRUD operations, tree building, and linkable
| item discovery. Used by the admin controller for menu management and
| by the public side for rendering navigation in themes.
|
| Access anywhere with: $app->navigation()
*/
$app->map('navigation', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\NavigationService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Settings Store
|--------------------------------------------------------------------------
| Database-backed runtime settings, the strongest source in the settings
| precedence chain (supersedes config files; .env/deployment values stay
| authoritative for undeclared keys). Only settings declared via adext
| type 'admin.settings' may be stored - secrets and infra keys can
| never enter this store (sole exception: Mail.password, which the
| Mailer service stores encrypted-at-rest).
|
| Registered BEFORE loadPlugins() so plugins can read/write settings
| during their register() calls. Rows load lazily: autoload rows come
| from one bulk query on first access, the rest per-key on miss.
|
| Access anywhere with: $app->settings()->get('CMS.siteName')
*/
$app->map('settings', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\SettingsService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Mailer (SMTP)
|--------------------------------------------------------------------------
| Outbound mail: resolves SMTP config from the settings store, builds and
| sends via PHPMailer, and records every attempt (sent/failed) through the
| Mail model. SMTP-only. The SMTP password is encrypted at rest.
|
| Access anywhere with: $app->mailer()->sendHtml($to, $subject, $body)
*/
$app->map('mailer', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\Mailer($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Captcha Service
|--------------------------------------------------------------------------
| Site-wide human verification: provider config from the Captcha.* settings
| namespace, per-area switches driven by the adext 'captcha.area' type, and
| the widget markup every protected form embeds. hCaptcha and reCAPTCHA v2
| are supported; server-side verification fails closed on misconfiguration.
|
| Access anywhere with: $app->captcha()->enforcedFor('comments')
*/
$app->map('captcha', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\CaptchaService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Content Service
|--------------------------------------------------------------------------
| Applies plugin-registered content.render transforms to rich-text bodies.
|
| Access anywhere with: $app->content()->render($html)
*/
$app->map('content', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\ContentService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Cron Service
|--------------------------------------------------------------------------
| Runs plugin-registered cron tasks. The root `cron` script, called by
| system crontabs at 1m, 4h, and 24h, boots the app and calls
| $app->cron()->run($interval). No web routes involved.
|
| Access anywhere with: $app->cron()->run('1m')
*/
$app->map('cron', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\CronService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Asset Service
|--------------------------------------------------------------------------
| Unified asset serving for themes, plugins, and vendor packages.
| Eliminates the need to copy assets to public/ directories.
|
| Access anywhere with: $app->asset()->resolve($type, $name, $path)
*/
$app->map('asset', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\AssetService($app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Default Timezone (applied after core-admin loads)
|--------------------------------------------------------------------------
| MOVED to bootstrap.php, after core-admin.php. The settings service's
| declaredFields() cache must not be populated before adext registrations
| exist (calling settings()->get() here would lock in an empty cache
| and no settings would ever save).
|
| @see bootstrap.php, timezone block after core-admin require
*/

/*
|--------------------------------------------------------------------------
| Shield Config (app/config/shield.php)
|--------------------------------------------------------------------------
| app/config/shield.php is a full copy of the flight-shield package config
| with Pubvana's defaults. Its hmac block is edited in place by the CLI
| (php runway shield:hmac). Folded here into the plugins store so the
| PluginLoader merges it over the package Config.php defaults.
*/
$shieldConfigFile = __DIR__ . $ds . 'shield.php';
if (is_file($shieldConfigFile)) {
    $shieldConfig = (array) require $shieldConfigFile;
    if (!empty($shieldConfig)) {
        $plugins = $app->get('plugins');
        if (!is_array($plugins)) {
            $plugins = [];
        }
        $plugins['enlivenapp/flight-shield'] = array_replace_recursive(
            $plugins['enlivenapp/flight-shield'] ?? [],
            $shieldConfig
        );
        $app->set('plugins', $plugins);
    }
}

/*
|--------------------------------------------------------------------------
| Trust Client
|--------------------------------------------------------------------------
| Client for the Pubvana trust service at pubvanacms.com. Collects the
| addons installed here, asks the check API for each one's standing, and
| caches the answers in trust_cache. The 4h cron (registered in
| core-admin.php) runs the TTL-gated batch; the admin plugins/themes pages
| and the activation gate read the cache or check live.
|
| Access anywhere with: $app->trustClient()->checkAddon(...)
*/
$app->map('trustClient', function () use ($app) {
    static $instance = null;
    if ($instance === null) {
        $instance = new \Pubvana\Services\TrustClientService($app->db(), $app);
    }
    return $instance;
});

/*
|--------------------------------------------------------------------------
| Plugin Loader
|--------------------------------------------------------------------------
| Scans plugin folders for pubvana.json, discovers what each plugin provides,
| registers routes with auto-prefixing, registers hooks with the
| extension registry, and calls Plugin::register() for custom setup.
|
| The loader runs migrations before loading plugins so database tables
| are up to date.
|
| @see app/Services/PluginLoader.php
*/
$pluginLoader = new \Pubvana\Services\PluginLoader(
    $app,
    $app->router(),
    PROJECT_ROOT . $ds . 'plugins',
    PROJECT_ROOT . $ds . 'vendor',
    // Read from the value the app holds (not the raw config array) so .env
    // overrides applied by bootstrap's envMap are included.
    $app->get('plugins') ?? []
);
$app->map('pluginLoader', fn() => $pluginLoader);
$pluginLoader->loadPlugins();

// CLI tooling (runway) reads the enabled-gated migration set as an app
// value via ConfigLoader, so disabled plugin migrations never run from the
// command line either. Same set, web and CLI.
$app->set('migrations', $pluginLoader->getMigrationConfig());

// CSRF middleware starts AFTER plugins: it reads/writes session data and
// must use the DB-backed session service from enlivenapp/flight-sessions.
//
// Sessionless API and webhook paths registered under the csrf.exempt
// extension type (e.g. the AI Assistant's /ai/* API) are excluded because
// they authenticate with per-request bearer keys or gateway signatures and
// cannot present a session CSRF token. Admin child pages still start with
// /admin and remain protected.
$requestPath = (string) parse_url($app->request()->url ?? '/', PHP_URL_PATH);
$csrfExempt = false;
foreach ($app->adext()->get('csrf.exempt', 'default') as $exempt) {
    $prefix = $exempt['prefix'] ?? '';
    if ($prefix !== '' && str_starts_with($requestPath, $prefix)) {
        $csrfExempt = true;
        break;
    }
}
if (!$csrfExempt) {
    $csrf->before();
}

// Captcha middleware runs immediately after CSRF so a rejected request is
// first answered by the CSRF gate. Like the CSRF gate, it is invoked inline
// during boot (after loadPlugins(), so captcha.area registrations exist) and
// reads the superglobals itself. It only acts on the core-owned sign-in POST;
// plugins enforce their own captcha areas inside their submission handlers.
$captchaMiddleware = new \Pubvana\Services\CaptchaMiddleware($app);
$captchaMiddleware->before();

/*
|--------------------------------------------------------------------------
| Global Error Handler
|--------------------------------------------------------------------------
| Catches any uncaught exception that reaches the top of the stack.
|
| - Logs every exception via error_log()
| - In development: re-throws so Tracy can display the debug page
| - In production: returns JSON for API/AJAX requests, HTML for browsers
| - Uses HTTP status codes from typed exceptions when available
*/
$app->map('error', function (\Throwable $e) use ($app): void {
    $status = 500;
    if (method_exists($e, 'getHttpStatus')) {
        $status = $e->getHttpStatus();
    }

    $class = get_class($e);

    if ($app->get('environment') === 'development') {
        // In development, re-throw so Tracy displays and logs the exception.
        throw $e;
    }

    // Production: log every exception to writable/logs/exception.log, the same
    // file Tracy uses, so application errors stay beside Tracy's logs. Flight's
    // default error_log() would go to the SAPI log instead.
    $logFile = PROJECT_ROOT . $ds . 'writable' . $ds . 'logs' . $ds . 'exception.log';
    $line = '[' . date('Y-m-d H-i-s') . "] [{$class}] {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    // Determine if the client wants JSON or HTML
    $request = $app->request();
    $wantsJson = str_contains($request->getHeader('Accept') ?? '', 'application/json')
        || str_contains($request->getHeader('Content-Type') ?? '', 'application/json')
        || ($request->ajax ?? false);

    if ($wantsJson) {
        $app->json(['error' => $e->getMessage()], $status);
    } else {
        $app->render('errors/error', [
            'status'  => $status,
            'message' => $e->getMessage(),
        ]);
        $app->response()->status($status);
    }
});
