<?php

/**
 * Core Admin - Registers core admin features via adext.
 *
 * This file registers menu items, routes, and dashboard contributions
 * for the built-in admin features: users, groups, permissions, regions.
 * Everything goes through adext - one system, not two.
 *
 * Security: Core routes are marked as isCore=true, which means:
 *   - Plugins cannot override these routes
 *   - Plugins cannot remove these routes
 *   - If a plugin tries to register the same route, it is rejected and logged
 *
 * Loaded after services.php (adext is available) and before routes.php.
 *
 * @package Pubvana\Config
 */

use Pubvana\Controllers\Admin\UsersController;
use Pubvana\Controllers\Admin\GroupsController;
use Pubvana\Controllers\Admin\PermissionsController;
use Pubvana\Controllers\Admin\ThemesController;
use Pubvana\Controllers\Admin\SettingsController;
use Pubvana\Controllers\Admin\NavigationController;
use Pubvana\Controllers\Admin\EmailAdminController;
use Pubvana\Controllers\Admin\LoginSecController;
use Pubvana\Controllers\Admin\CaptchaAdminController;

use Pubvana\Controllers\Admin\PluginsController;

use Enlivenapp\FlightShield\Middlewares\ForcePasswordResetMiddleware;

/** @var \flight\Engine $app */
$app = $app ?? Flight::app();
$adext = $app->adext();


/*
|--------------------------------------------------------------------------
| Admin Menu Items
|--------------------------------------------------------------------------
| Register core admin menu items. These are core items
| and cannot be overridden by plugins. Plugins can ADD to the slots,
| but cannot modify or remove these items.
*/

// Batch registration - single call per slot
$adext->register('admin.menu', 'settings', [
    'pubvana.settings' => [
        'label'    => 'General',
        'icon'     => 'ti-settings',
        'url'      => '/settings',
        'priority' => 1,
        'core'     => true,
    ],
    'pubvana.login_sec' => [
        'label'    => 'Login',
        'icon'     => 'ti-login',
        'url'      => '/login-sec',
        'priority' => 2,
        'core'     => true,
    ],
    'pubvana.captcha' => [
        'label'    => 'Captcha',
        'icon'     => 'ti-shield-check',
        'url'      => '/captcha',
        'priority' => 3,
        'core'     => true,
    ],
    'pubvana.users' => [
        'label'    => 'Users',
        'icon'     => 'ti-users',
        'url'      => '/users',
        'priority' => 10,
        'core'     => true,
    ],
    'pubvana.groups' => [
        'label'    => 'Groups',
        'icon'     => 'ti-users-group',
        'url'      => '/groups',
        'priority' => 11,
        'core'     => true,
    ],
    'pubvana.permissions' => [
        'label'    => 'Permissions',
        'icon'     => 'ti-lock',
        'url'      => '/permissions',
        'priority' => 12,
        'core'     => true,
    ],
    'pubvana.plugins' => [
        'label'    => 'Plugins',
        'icon'     => 'ti-puzzle',
        'url'      => '/plugins',
        'priority' => 13,
        'core'     => true,
    ],
]);



/*
|--------------------------------------------------------------------------
| Appearance Menu Item
|--------------------------------------------------------------------------
| Theme and region management lives under the Appearance menu slot.
*/
$adext->register('admin.menu', 'appearance', 'pubvana.themes', [
    'label'    => 'Themes',
    'icon'     => 'ti-palette',
    'url'      => '/themes',
    'priority' => 10,
    'submenu'  => [
        'list' => [
            'label'    => 'All Themes',
            'url'      => '/themes',
            'priority' => 10,
        ],
        'regions' => [
            'label'    => 'Regions',
            'url'      => '/themes/regions',
            'priority' => 20,
        ],
    ],
]);

$adext->register('admin.menu', 'appearance', 'pubvana.navigation', [
    'label'    => 'Navigation',
    'icon'     => 'ti-menu-2',
    'url'      => '/navigation',
    'priority' => 20,
]);

/*
|--------------------------------------------------------------------------
| Tools Menu Item
|--------------------------------------------------------------------------
| Outbound mail lives in the Tools slot, alongside plugin tools items
| (e.g. Redirects' URL Manager). Serves the standalone email page.
*/
$adext->register('admin.menu', 'tools', 'pubvana.cms.mail', [
    'label'    => 'Email',
    'icon'     => 'ti-mail',
    'url'      => '/email',
    'priority' => 30,
    'core'     => true,
]);

/*
|--------------------------------------------------------------------------
| Admin Routes (Core - Cannot Be Overridden)
|--------------------------------------------------------------------------
| Register core admin CRUD routes via adext.
| The /admin prefix is auto-applied by adext for admin.* types.
| Auth middleware is applied per-route.
|
| isCore=true means these routes cannot be overridden by plugins.
| If a plugin tries to register the same route, it will be rejected
| and a warning will be logged.
*/

/*
|--------------------------------------------------------------------------
| Auth Middleware (Disabled For Development)
|--------------------------------------------------------------------------
| Set to null so admin pages are reachable without a browser session.
|
| To reinstate protection, swap null for Shield middlewares:
|
|   use Enlivenapp\FlightShield\Middlewares\GroupMiddleware;
|   use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
|
|   // Blanket group gate on every admin route:
|   $authMiddleware = new GroupMiddleware($app, 'admin', 'superadmin');
|
|   // ...or per-route permission gates matching Shield's seeded perms:
|   ['GET', '/users', [UsersController::class, 'index'],
|       [new SessionAuthMiddleware($app), new PermissionMiddleware($app, 'users.list')]],
|
| adext skips non-object middleware entries, so null placeholders are safe.
*/
$authMiddleware = null;

/*
|--------------------------------------------------------------------------
| Forced Password Reset Gate
|--------------------------------------------------------------------------
| Redirects LOGGED-IN users whose Shield email identity carries the
| force_reset flag to /auth/reset-password until they save a new password.
| Anonymous traffic passes straight through, so this adds no auth
| protection on its own; it rides along on every admin route below.
| After an admin sets Force Reset on a user, the user gets bounced here
| on their next admin visit.
*/
$forceResetMiddleware = new ForcePasswordResetMiddleware($app);

// Users
$adext->addRoutes('admin', [
    ['GET',    '/users',              [UsersController::class, 'index'],   [$authMiddleware]],
    ['GET',    '/users/create',       [UsersController::class, 'create'],  [$authMiddleware]],
    ['POST',   '/users/store',        [UsersController::class, 'store'],   [$authMiddleware]],
    ['POST',   '/users/invite',       [UsersController::class, 'invite'],  [$authMiddleware]],
    ['GET',    '/users/@id/edit',     [UsersController::class, 'edit'],    [$authMiddleware]],
    ['POST',   '/users/@id/update',   [UsersController::class, 'update'],  [$authMiddleware]],
    ['POST',   '/users/@id/delete',   [UsersController::class, 'delete'],  [$authMiddleware]],
    ['POST',   '/users/@id/toggle',   [UsersController::class, 'toggle'],  [$authMiddleware]],
    ['POST',   '/users/@id/ban',      [UsersController::class, 'ban'],     [$authMiddleware]],
    ['POST',   '/users/@id/unban',    [UsersController::class, 'unban'],   [$authMiddleware]],
    ['POST',   '/users/@id/force-reset', [UsersController::class, 'forceReset'], [$authMiddleware, $forceResetMiddleware]],
], 'pubvana.core', true);

// Groups
$adext->addRoutes('admin', [
    ['GET',    '/groups',             [GroupsController::class, 'index'],   [$authMiddleware]],
    ['GET',    '/groups/create',      [GroupsController::class, 'create'],  [$authMiddleware]],
    ['POST',   '/groups/store',       [GroupsController::class, 'store'],   [$authMiddleware]],
    ['GET',    '/groups/@id/edit',    [GroupsController::class, 'edit'],    [$authMiddleware]],
    ['POST',   '/groups/@id/update',  [GroupsController::class, 'update'],  [$authMiddleware]],
    ['POST',   '/groups/@id/delete',  [GroupsController::class, 'delete'],  [$authMiddleware]],
], 'pubvana.core', true);

// Permissions
$adext->addRoutes('admin', [
    ['GET',    '/permissions',              [PermissionsController::class, 'index'],   [$authMiddleware]],
    ['GET',    '/permissions/create',       [PermissionsController::class, 'create'],  [$authMiddleware]],
    ['POST',   '/permissions/store',        [PermissionsController::class, 'store'],   [$authMiddleware]],
    ['POST',   '/permissions/@id/delete',   [PermissionsController::class, 'delete'],  [$authMiddleware]],
], 'pubvana.core', true);

// Themes & Regions
$adext->addRoutes('admin', [
    ['GET',    '/themes',                      [ThemesController::class, 'index'],            [$authMiddleware]],
    ['POST',   '/themes/@id/activate',         [ThemesController::class, 'activate'],         [$authMiddleware]],
    ['GET',    '/themes/@id/options',          [ThemesController::class, 'options'],          [$authMiddleware]],
    ['POST',   '/themes/@id/options',          [ThemesController::class, 'saveOptions'],      [$authMiddleware]],
    ['GET',    '/themes/regions',              [ThemesController::class, 'regions'],          [$authMiddleware]],
    ['POST',   '/themes/regions/place',        [ThemesController::class, 'placeBlock'],       [$authMiddleware]],
    ['POST',   '/themes/regions/remove',       [ThemesController::class, 'removePlacement'],  [$authMiddleware]],
    ['POST',   '/themes/regions/reorder',      [ThemesController::class, 'reorderPlacements'],[$authMiddleware]],
    ['POST',   '/themes/regions/move',         [ThemesController::class, 'movePlacement'],    [$authMiddleware]],
    ['POST',   '/themes/regions/values',       [ThemesController::class, 'saveBlockValues'],  [$authMiddleware]],
], 'pubvana.core', true);

// Settings (General page - tabbed)
$adext->addRoutes('admin', [
    ['GET',  '/settings',       [SettingsController::class, 'general'], [$authMiddleware, $forceResetMiddleware]],
    ['POST', '/settings/save',  [SettingsController::class, 'save'],    [$authMiddleware]],
], 'pubvana.core', true);

// Login (Shield sign-in features - Settings > Login)
$adext->addRoutes('admin', [
    ['GET',  '/login-sec',       [LoginSecController::class, 'index'], [$authMiddleware, $forceResetMiddleware]],
    ['POST', '/login-sec/save',  [LoginSecController::class, 'save'],  [$authMiddleware]],
], 'pubvana.core', true);

// Captcha (site-wide human verification - Settings > Captcha)
$adext->addRoutes('admin', [
    ['GET',  '/captcha',       [CaptchaAdminController::class, 'index'], [$authMiddleware, $forceResetMiddleware]],
    ['POST', '/captcha/save',  [CaptchaAdminController::class, 'save'],  [$authMiddleware]],
], 'pubvana.core', true);

// Email (SMTP settings - Tools > Email)
$adext->addRoutes('admin', [
    ['GET',  '/email',       [EmailAdminController::class, 'index'], [$authMiddleware, $forceResetMiddleware]],
    ['POST', '/email/save',  [EmailAdminController::class, 'save'],  [$authMiddleware]],
    ['POST', '/email/test',  [EmailAdminController::class, 'test'],  [$authMiddleware]],
], 'pubvana.core', true);

// Plugins (enable/disable + priority)
$adext->addRoutes('admin', [
    ['GET',  '/plugins',      [PluginsController::class, 'index'], [$authMiddleware, $forceResetMiddleware]],
    ['POST', '/plugins/save', [PluginsController::class, 'save'], [ $authMiddleware]],
], 'pubvana.core', true);

// Navigation
$adext->addRoutes('admin', [
    ['GET',  '/navigation',              [NavigationController::class, 'index'],   [$authMiddleware]],
    ['POST', '/navigation/store',        [NavigationController::class, 'store'],   [$authMiddleware]],
    ['POST', '/navigation/@id/delete',   [NavigationController::class, 'delete'],  [$authMiddleware]],
    ['POST', '/navigation/reorder',      [NavigationController::class, 'reorder'], [$authMiddleware, $forceResetMiddleware]],
], 'pubvana.core', true);

/*
|--------------------------------------------------------------------------
| Core Settings Declarations
|--------------------------------------------------------------------------
| The core "Site" tab on the General settings page. These declarations:
|   - Define the ONLY CMS keys savable through the settings UI
|   - Provide labels, input types, and defaults for rendering
|   - Map each key to its existing config fallback so values resolve
|     down the chain (.env > .env/config.php) until a row is saved.
|
| Deliberately NOT declared here: deployment/infra keys. SITE_URL stays
| env/config-only (it pins deployments); SESSION_ENCRYPTION_KEY and DB
| credentials are never declarable at all.
|
| Plugins add their own tabs via:
|   $app->adext()->register('admin.settings', 'general', 'pubvana.blog', [...]);
*/

// Timezone identifiers for the select field (label = value)
$timezones = DateTimeZone::listIdentifiers();

// Published pages for the homepage page selector are fetched lazily by
// SettingsController (Page::getPublishedOptions()) when the admin Settings
// form renders or saves. No query here: this is boot-time, and public
// requests have no business reading the pages catalog.

$adext->register('admin.settings', 'general', 'pubvana.cms.site', [
    'label'       => 'Site',
    'description' => 'Core site identity and locale.',
    'priority'    => 10,
    'fields'      => [
        [
            'key'         => 'CMS.siteName',
            'label'       => 'Site Name',
            'type'        => 'text',
            'default'     => 'Pubvana',
            'description' => 'Shown in the admin top bar and across the site.',
        ],
        [
            'key'         => 'CMS.siteByline',
            'label'       => 'Site byline',
            'type'        => 'text',
            'default'     => 'Publishing Nirvana',
            'description' => 'Shown under the site name publically.',
        ],
        [
            'key'         => 'CMS.siteUrl',
            'label'       => 'Site URL',
            'type'        => 'text',
            'description' => 'Absolute base URL used for generated links and emails.',
        ],
        [
            'key'         => 'CMS.logo',
            'label'       => 'Site Logo',
            'type'        => 'text',
            'description' => 'The site\'s logo shown in various areas',
        ],
        [
            'key'         => 'CMS.favicon',
            'label'       => 'Site FavIcon',
            'type'        => 'text',
            'description' => 'Shows you favicon in browser tabs (default is Pubvana favicon)',
        ],
        [
            'key'         => 'CMS.copyright',
            'label'       => 'Site Copyright',
            'type'        => 'text',
            'description' => 'Text that follows (c) and date: EG: Your Company (Defaults to Site Name above)',
        ],
        [
            'key'         => 'CMS.adminEmail',
            'label'       => 'Admin Email',
            'type'        => 'email',
            'description' => 'Contact address for system notifications.',
        ],
        [
            'key'         => 'CMS.defaultTimezone',
            'label'       => 'Timezone',
            'type'        => 'select',
            'options'     => array_combine($timezones, $timezones),
            'default'     => 'UTC',
            'description' => 'Applies system-wide. Takes effect on the next request.',
        ],
        [
            'key'         => 'CMS.homepageType',
            'label'       => 'Homepage',
            'type'        => 'select',
            'options'     => ['blog' => 'Blog Feed', 'pages' => 'Static Page'],
            'default'     => 'blog',
            'description' => 'What displays on the site root. Blog shows the latest posts; Pages lets you pick a static page.',
        ],
        [
            'key'         => 'CMS.homepagePageId',
            'label'       => 'Homepage Page',
            'type'        => 'select',
            'options'     => [],
            'default'     => null,
            'description' => 'Which published page to show when Homepage is set to Static Page.',
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Email Settings Declarations
|--------------------------------------------------------------------------
| The standalone Tools > Email page. These Mail.* keys are the ONLY email
| keys savable through the settings UI. Mail.username / Mail.password are
| the exception to the no-secrets-in-store rule: the Mailer service keeps
| the password encrypted at rest (AES, keyed by SESSION_ENCRYPTION_KEY).
| 'none' | 'tls' | 'ssl' mirrors PHPMailer's SMTPSecure values.
*/
$adext->register('admin.settings', 'email', 'pubvana.cms.mail', [
    'label'       => 'Email',
    'description' => 'SMTP transport and message defaults.',
    'priority'    => 20,
    'fields'      => [
        [
            'key'         => 'Mail.fromEmail',
            'label'       => 'From Email',
            'type'        => 'email',
            'default'     => null,
            'description' => 'Sender address shown to recipients. Defaults to the Admin Email.',
        ],
        [
            'key'         => 'Mail.fromName',
            'label'       => 'From Name',
            'type'        => 'text',
            'default'     => null,
            'description' => 'Display name for the sender. Defaults to the Site Name.',
        ],
        [
            'key'         => 'Mail.host',
            'label'       => 'SMTP Host',
            'type'        => 'text',
            'default'     => 'localhost',
            'description' => 'SMTP server hostname.',
        ],
        [
            'key'         => 'Mail.port',
            'label'       => 'SMTP Port',
            'type'        => 'number',
            'default'     => 587,
            'description' => 'Common ports: 587 (STARTTLS), 465 (implicit SSL), 25 (local relay).',
        ],
        [
            'key'         => 'Mail.encryption',
            'label'       => 'Encryption',
            'type'        => 'select',
            'options'     => [
                'none' => 'None (plaintext)',
                'tls'  => 'TLS (STARTTLS)',
                'ssl'  => 'SSL (implicit)',
            ],
            'default'     => 'tls',
            'description' => 'Transport encryption. TLS is the recommended default.',
        ],
        [
            'key'         => 'Mail.username',
            'label'       => 'SMTP Username',
            'type'        => 'text',
            'default'     => null,
            'description' => 'SMTP auth username. Leave blank for no authentication.',
        ],
        [
            'key'         => 'Mail.password',
            'label'       => 'SMTP Password',
            'type'        => 'password',
            'default'     => null,
            'description' => 'SMTP auth password. Stored encrypted. Leave blank to keep the current password.',
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Login Settings Declarations
|--------------------------------------------------------------------------
| The standalone Settings > Login page (LoginSecController). These
| Shield.* keys are the ONLY login settings savable through the admin UI.
| The PluginLoader folds stored rows onto the flight-shield config at
| boot (after migrations, before Shield registers), so changes apply on
| the next request. The toggles map to Shield config:
|
|   Shield.email_2fa          -> actions.login
|   Shield.email_activation   -> actions.register
|   Shield.allow_registration -> allow_registration
|   Shield.magic_link         -> allow_magic_link
|   Shield.remember_me        -> session.allow_remembering
|
| Email 2FA, email activation, and magic link need a working SMTP setup
| (Tools > Email); the Login page says so next to those toggles.
*/
$adext->register('admin.settings', 'login_sec', 'pubvana.cms.login_sec', [
    'label'       => 'Login',
    'description' => 'Sign-in and registration features for the login area.',
    'priority'    => 30,
    'fields'      => [
        [
            'key'         => 'Shield.allow_registration',
            'label'       => 'Allow public registration',
            'type'        => 'checkbox',
            'default'     => true,
            'description' => 'People can create their own accounts on the Register page. Turn off to only create accounts from the admin area.',
        ],
        [
            'key'         => 'Shield.magic_link',
            'label'       => 'Magic link login',
            'type'        => 'checkbox',
            'default'     => false,
            'description' => 'People can sign in with a one-time link emailed to them instead of typing a password. Needs email delivery set up (Tools > Email).',
        ],
        [
            'key'         => 'Shield.remember_me',
            'label'       => 'Remember me',
            'type'        => 'checkbox',
            'default'     => true,
            'description' => 'Adds a Remember me option to the login form so people stay signed in on their usual device for 30 days.',
        ],
        [
            'key'         => 'Shield.email_2fa',
            'label'       => 'Email two-factor (2FA)',
            'type'        => 'checkbox',
            'default'     => false,
            'description' => 'Sign-in becomes two steps: after the password, users enter a one-time code emailed to them. A stolen password alone cannot get into the account. Needs email delivery set up (Tools > Email).',
        ],
        [
            'key'         => 'Shield.email_activation',
            'label'       => 'Email activation on register',
            'type'        => 'checkbox',
            'default'     => false,
            'description' => 'New accounts start turned off. New users get an email with an activation link and can sign in only after clicking it. Needs email delivery set up (Tools > Email).',
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Captcha Settings Declarations
|--------------------------------------------------------------------------
| The standalone Settings > Captcha page (CaptchaAdminController). These
| Captcha.* keys are the ONLY captcha keys savable through the admin UI.
| The page also shows a switch per registered 'captcha.area'; the list of
| switched-on areas is stored as one JSON row (Captcha.protected) written
| by the controller, not per-key declarations.
|
| hCaptcha and reCAPTCHA v2 (checkbox) are the supported providers; their
| endpoints and widget details live in CaptchaService.
*/
$adext->register('admin.settings', 'captcha', 'pubvana.cms.captcha', [
    'label'       => 'Captcha',
    'description' => 'Human verification for forms across the site.',
    'priority'    => 40,
    'fields'      => [
        [
            'key'         => 'Captcha.provider',
            'label'       => 'Provider',
            'type'        => 'select',
            'options'     => [
                'none'      => 'None',
                'hcaptcha'  => 'hCaptcha',
                'recaptcha' => 'reCAPTCHA v2 (Google)',
            ],
            'default'     => 'none',
            'description' => 'Service that checks the visitor is human. Sign up at the provider to get a site key and secret key.',
        ],
        [
            'key'         => 'Captcha.site_key',
            'label'       => 'Site key',
            'type'        => 'text',
            'default'     => '',
            'description' => 'Public site key from the provider dashboard. Shown in the page with the captcha.',
        ],
        [
            'key'         => 'Captcha.secret_key',
            'label'       => 'Secret key',
            'type'        => 'password',
            'default'     => '',
            'description' => 'Secret key from the provider dashboard. Used server-side to verify answers. Leave blank to keep the current value.',
        ],
    ],
]);

/*
|--------------------------------------------------------------------------
| Captcha Area (Core)
|--------------------------------------------------------------------------
| The sign-in form is a captcha-protected area owned by core. Plugins
| register their own areas (comment forms, public forms) in their
| Plugin.php. Each area can be switched on or off in Settings > Captcha;
| CaptchaMiddleware enforces the switch on the login POST.
*/
$adext->register('captcha.area', 'default', 'login', [
    'label'       => 'Sign-in form',
    'description' => 'Requires a completed captcha before a sign-in attempt is processed.',
    'priority'    => 10,
]);

/*
|--------------------------------------------------------------------------
| Dashboard Contributions (Core)
|--------------------------------------------------------------------------
| Users (Shield) cards and the Quick Actions section. Core contributions
| use the pubvana.core source key. Group tokens ('people', 'content',
| 'media', 'system') are resolved and labeled in AdminController.
*/

// Users cards — total, active, new (with trend), banned, inactive
$adext->register('admin.dashboard', 'cards', 'pubvana.users', [
    'label'    => 'Users',
    'priority' => 10,
    'callable' => function (array $context) use ($app): array {
        $total = 0;
        $active = 0;
        $inactive = 0;
        $banned = 0;
        $newThisMonth = 0;
        $newChange = 0.0;

        try {
            $stats = $app->auth()->stats();
            $total = $stats->totalUsers();
            $active = $stats->activeUsers();
            $inactive = $stats->inactiveUsers();
            $banned = $stats->bannedUsers();
            $newThisMonth = $stats->newUsersThisMonth();
            $newChange = $stats->newUsersPercentChange();
        } catch (\Throwable $e) {
            $total = $app->auth()->users()->count();
            $active = $total;
        }

        $changeDirection = $newChange >= 0 ? 'up' : 'down';
        $changeLabel = ($newChange >= 0 ? '+' : '') . $newChange . '%';

        return [
            [
                'id'          => 'total-users',
                'label'       => 'Users',
                'value'       => (int) $total,
                'icon'        => 'ti-users',
                'tone'        => 'primary',
                'group'       => 'people',
                'href'        => '/users',
                'description' => 'Registered accounts on the site.',
            ],
            [
                'id'          => 'active-users',
                'label'       => 'Active Users',
                'value'       => (int) $active,
                'icon'        => 'ti-user-check',
                'tone'        => 'success',
                'group'       => 'people',
                'href'        => '/users',
                'description' => 'Accounts currently allowed to log in.',
            ],
            [
                'id'          => 'inactive-users',
                'label'       => 'Inactive Users',
                'value'       => (int) $inactive,
                'icon'        => 'ti-user-off',
                'tone'        => 'secondary',
                'group'       => 'people',
                'href'        => '/users',
                'description' => 'Deactivated accounts awaiting reactivation.',
            ],
            [
                'id'          => 'banned-users',
                'label'       => 'Banned Users',
                'value'       => (int) $banned,
                'icon'        => 'ti-user-x',
                'tone'        => 'danger',
                'group'       => 'people',
                'href'        => '/users',
                'description' => 'Accounts blocked from logging in.',
            ],
            [
                'id'          => 'new-users',
                'label'       => 'New Users',
                'value'       => (int) $newThisMonth,
                'icon'        => 'ti-user-plus',
                'tone'        => 'info',
                'group'       => 'people',
                'href'        => '/users',
                'description' => 'Registrations this month.',
                'trend'       => [
                    'direction' => $changeDirection,
                    'value'     => $changeLabel,
                    'label'     => 'vs last month',
                ],
            ],
        ];
    },
]);

// Login activity section — Shield auth_logins summary
$adext->register('admin.dashboard', 'sections', 'pubvana.logins', [
    'label'    => 'Login Activity',
    'priority' => 20,
    'callable' => static function (array $context) use ($app): array {
        $summary = ['total' => 0, 'success' => 0, 'failed' => 0];

        try {
            $summary = $app->auth()->stats()->loginAttempts(30);
        } catch (\Throwable $e) {
            // Stats table missing (fresh install) — zeros are fine.
        }

        return [[
            'id'          => 'login-activity',
            'title'       => 'Login Activity',
            'type'        => 'list',
            'icon'        => 'ti-login',
            'col'         => 'col-12 col-xl-6',
            'group'       => 'people',
            'description' => 'Authentication attempts over the last 30 days, including password reset requests.',
            'href'        => '/users',
            'items'       => [
                [
                    'label' => 'Successful sign-ins',
                    'meta'  => (string) ($summary['success'] ?? 0),
                ],
                [
                    'label' => 'Failed attempts',
                    'meta'  => (string) ($summary['failed'] ?? 0),
                ],
                [
                    'label' => 'Total attempts',
                    'meta'  => (string) ($summary['total'] ?? 0),
                ],
            ],
        ]];
    },
]);

// Quick Actions section
$adext->register('admin.dashboard', 'sections', 'pubvana.admin', [
    'label'    => 'Quick Actions',
    'priority' => 90,
    'callable' => static function (array $context): array {
        return [[
            'id'          => 'quick-actions',
            'title'       => 'Quick Actions',
            'type'        => 'actions',
            'icon'        => 'ti-bolt',
            'col'         => 'col-12',
            'group'       => 'system',
            'description' => 'Common admin tasks you are likely to need next.',
            'items'       => [
                [
                    'label'    => 'New Page',
                    'href'     => '/pages/create',
                    'icon'     => 'ti-file-plus',
                    'emphasis' => 'primary',
                ],
                [
                    'label'    => 'New Post',
                    'href'     => '/blog/create',
                    'icon'     => 'ti-article',
                    'emphasis' => 'primary',
                ],
                [
                    'label'    => 'New User',
                    'href'     => '/users/create',
                    'icon'     => 'ti-user-plus',
                    'emphasis' => 'primary',
                ],
                [
                    'label'    => 'Upload Media',
                    'href'     => '/media',
                    'icon'     => 'ti-upload',
                    'emphasis' => 'primary',
                ],
            ],
        ]];
    },
]);
