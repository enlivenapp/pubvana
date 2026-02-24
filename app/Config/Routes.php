<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ===================================================
// PUBLIC ROUTES
// ===================================================
$routes->get('/',                           'Blog::index');
$routes->get('blog',                        'Blog::index');
$routes->get('blog/(:segment)',             'Blog::post/$1');
$routes->post('blog/(:segment)',            'Blog::post/$1');   // comment submission
$routes->get('category/(:segment)',         'Blog::category/$1');
$routes->get('tag/(:segment)',              'Blog::tag/$1');
$routes->get('archive/(:num)/(:num)',       'Blog::archive/$1/$2');
$routes->get('search',                      'Search::index');
$routes->get('sitemap.xml',                 'Sitemap::index');
$routes->get('news-sitemap.xml',            'NewsSitemap::index');
$routes->get('robots.txt',                  'Sitemap::robots');
$routes->get('feed',                        'Feed::index');
$routes->get('contact',                     'Contact::index');
$routes->post('contact',                    'Contact::send');
$routes->get('preview/(:segment)',          'Blog::preview/$1');
$routes->get('go/(:segment)',               'AffiliateRedirect::go/$1');

// ===================================================
// SHIELD AUTH (login, register, logout, forgot-password, etc.)
// ===================================================
service('auth')->routes($routes);

// Social OAuth
$routes->get('auth/social/(:segment)',          'SocialAuth::redirect/$1');
$routes->get('auth/social/(:segment)/callback', 'SocialAuth::callback/$1');

// TOTP 2FA verification (post-login, before admin access)
$routes->get('auth/2fa',  'TwoFactor::verify');
$routes->post('auth/2fa', 'TwoFactor::verify');

// ===================================================
// ADMIN ROUTES
// ===================================================
$routes->group('admin', ['filter' => ['admin_auth', 'totp'], 'namespace' => 'App\Controllers\Admin'], function ($routes) {

    // Dashboard
    $routes->get('',                         'Dashboard::index');

    // Posts
    $routes->get('posts',                    'Posts::index');
    $routes->get('posts/create',             'Posts::create');
    $routes->post('posts/create',            'Posts::store');
    $routes->post('posts/bulk',              'Posts::bulk');
    $routes->get('posts/(:num)/edit',        'Posts::edit/$1');
    $routes->post('posts/(:num)/edit',       'Posts::update/$1');
    $routes->post('posts/(:num)/delete',     'Posts::delete/$1');

    // Post Revisions
    $routes->get('posts/(:num)/revisions',          'Revisions::index/$1');
    $routes->get('posts/revisions/(:num)',           'Revisions::show/$1');
    $routes->post('posts/revisions/(:num)/restore',  'Revisions::restore/$1');

    // Pages
    $routes->get('pages',                    'Pages::index');
    $routes->get('pages/create',             'Pages::create');
    $routes->post('pages/create',            'Pages::store');
    $routes->get('pages/(:num)/edit',        'Pages::edit/$1');
    $routes->post('pages/(:num)/edit',       'Pages::update/$1');
    $routes->post('pages/(:num)/delete',     'Pages::delete/$1');

    // Categories
    $routes->get('categories',               'Categories::index');
    $routes->get('categories/create',        'Categories::create');
    $routes->post('categories/create',       'Categories::store');
    $routes->get('categories/(:num)/edit',   'Categories::edit/$1');
    $routes->post('categories/(:num)/edit',  'Categories::update/$1');
    $routes->post('categories/(:num)/delete','Categories::delete/$1');

    // Tags
    $routes->get('tags',                     'Tags::index');
    $routes->post('tags/(:num)/delete',      'Tags::delete/$1');

    // Comments
    $routes->get('comments',                  'Comments::index');
    $routes->post('comments/(:num)/approve',  'Comments::approve/$1');
    $routes->post('comments/(:num)/spam',     'Comments::spam/$1');
    $routes->post('comments/(:num)/trash',    'Comments::trash/$1');
    $routes->post('comments/(:num)/delete',   'Comments::delete/$1');

    // Media
    $routes->get('media',                    'Media::index');
    $routes->post('media/upload',            'Media::upload');
    $routes->post('media/(:num)/delete',     'Media::delete/$1');

    // Themes
    $routes->get('themes',                         'Themes::index');
    $routes->post('themes/(:num)/activate',        'Themes::activate/$1');
    $routes->get('themes/(:num)/options',          'Themes::options/$1');
    $routes->post('themes/(:num)/options',         'Themes::saveOptions/$1');

    // Widgets
    $routes->get('widgets',                        'Widgets::areas');
    $routes->post('widgets/add',                   'Widgets::addToArea');
    $routes->post('widgets/(:num)/remove',         'Widgets::removeFromArea/$1');
    $routes->get('widgets/(:num)/configure',       'Widgets::configure/$1');
    $routes->post('widgets/(:num)/configure',      'Widgets::saveConfig/$1');
    $routes->post('widgets/reorder',               'Widgets::reorder');

    // Navigation
    $routes->get('navigation',                     'Navigation::index');
    $routes->post('navigation/store',              'Navigation::store');
    $routes->post('navigation/(:num)/delete',      'Navigation::delete/$1');
    $routes->post('navigation/reorder',            'Navigation::reorder');

    // Users
    $routes->get('users',                    'Users::index');
    $routes->get('users/create',             'Users::create');
    $routes->post('users/create',            'Users::store');
    $routes->get('users/(:num)/edit',        'Users::edit/$1');
    $routes->post('users/(:num)/edit',       'Users::update/$1');
    $routes->post('users/(:num)/delete',     'Users::delete/$1');
    $routes->get('users/(:num)/profile',     'Users::profile/$1');
    $routes->post('users/(:num)/profile',    'Users::saveProfile/$1');
    $routes->get('users/2fa/setup',          'TwoFactor::setup');
    $routes->post('users/2fa/confirm',       'TwoFactor::confirm');
    $routes->post('users/2fa/disable',       'TwoFactor::disable');

    // Settings
    $routes->get('settings',                 'Settings::index');
    $routes->get('premium',                  'Settings::premiumPage');
    $routes->post('settings/general',        'Settings::saveGeneral');
    $routes->post('settings/seo',            'Settings::saveSeo');
    $routes->post('settings/email',          'Settings::saveEmail');
    $routes->post('settings/social',         'Settings::saveSocial');
    $routes->post('settings/sharing',        'Settings::saveSocialSharing');
    $routes->post('settings/premium',        'Settings::savePremium');

    // Social
    $routes->get('social',                   'Social::index');
    $routes->post('social/store',            'Social::store');
    $routes->post('social/(:num)/delete',    'Social::delete/$1');
    $routes->post('social/(:num)/toggle',    'Social::toggle/$1');

    // Redirects
    $routes->get('redirects',                'Redirects::index');
    $routes->post('redirects/store',         'Redirects::store');
    $routes->post('redirects/(:num)/delete', 'Redirects::delete/$1');

    // Marketplace
    $routes->get('marketplace',              'Marketplace::index');
    $routes->get('marketplace/themes',       'Marketplace::themes');
    $routes->get('marketplace/widgets',      'Marketplace::widgets');
    $routes->post('marketplace/install',     'Marketplace::install');
    $routes->post('marketplace/refresh',     'Marketplace::refresh');
    $routes->post('marketplace/update/(:segment)', 'Marketplace::update/$1');

    // Schedule
    $routes->get('schedule',                 'Schedule::view');
    $routes->get('schedule/events',          'Schedule::index');

    // Updates
    $routes->get('updates',                  'Updates::index');
    $routes->post('updates/check',           'Updates::check');

    // Analytics
    $routes->get('analytics',                'Analytics::index');
    $routes->get('analytics/data',           'Analytics::data');

    // Activity Log
    $routes->get('activity-log',             'ActivityLog::index');

    // Backup & Export
    $routes->get('backup',                   'Backup::index');
    $routes->post('backup/download',         'Backup::download');
    $routes->post('backup/delete',           'Backup::deleteFile');

    // Broken Links
    $routes->get('broken-links',                        'BrokenLinks::index');
    $routes->post('broken-links/(:num)/recheck',        'BrokenLinks::recheck/$1');
    $routes->post('broken-links/(:num)/dismiss',        'BrokenLinks::dismiss/$1');

    // Affiliate Links
    $routes->get('affiliates',               'Affiliates::index');
    $routes->get('affiliates/create',        'Affiliates::create');
    $routes->post('affiliates/create',       'Affiliates::store');
    $routes->get('affiliates/(:num)/edit',   'Affiliates::edit/$1');
    $routes->post('affiliates/(:num)/edit',  'Affiliates::update/$1');
    $routes->post('affiliates/(:num)/delete','Affiliates::delete/$1');
    $routes->get('affiliates/(:num)/clicks', 'Affiliates::clicks/$1');

    // Import
    $routes->get('import',                   'Import::index');
    $routes->post('import',                  'Import::upload');

    // Store
    $routes->get('store',                    'Store::index');
    $routes->post('store/install',           'Store::install');
});

// ===================================================
// 404 OVERRIDE — returns a proper 404 response instead of re-throwing
// the exception (required for FeatureTestTrait to capture assertStatus(404))
// ===================================================
$routes->set404Override(static function (): void {
    service('response')->setStatusCode(404)->send();
});

// ===================================================
// PLUGIN ROUTES — each plugin may provide its own Config/Routes.php
// ===================================================
foreach (glob(ROOTPATH . 'plugins/*/Config/Routes.php') as $pluginRoutes) {
    require $pluginRoutes;
}

// ===================================================
// CATCH-ALL: Static pages (must be last)
// ===================================================
$routes->get('(:segment)', 'Pages::show/$1');
