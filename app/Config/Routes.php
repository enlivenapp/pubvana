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
$routes->get('robots.txt',                  'Sitemap::robots');
$routes->get('feed',                        'Feed::index');
$routes->get('contact',                     'Contact::index');
$routes->post('contact',                    'Contact::send');

// ===================================================
// SHIELD AUTH (login, register, logout, forgot-password, etc.)
// ===================================================
service('auth')->routes($routes);

// ===================================================
// ADMIN ROUTES
// ===================================================
$routes->group('admin', ['filter' => 'admin_auth', 'namespace' => 'App\Controllers\Admin'], function ($routes) {

    // Dashboard
    $routes->get('',                         'Dashboard::index');

    // Posts
    $routes->get('posts',                    'Posts::index');
    $routes->get('posts/create',             'Posts::create');
    $routes->post('posts/create',            'Posts::store');
    $routes->get('posts/(:num)/edit',        'Posts::edit/$1');
    $routes->post('posts/(:num)/edit',       'Posts::update/$1');
    $routes->post('posts/(:num)/delete',     'Posts::delete/$1');

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
    $routes->get('users/(:num)/edit',        'Users::edit/$1');
    $routes->post('users/(:num)/edit',       'Users::update/$1');
    $routes->post('users/(:num)/delete',     'Users::delete/$1');

    // Settings
    $routes->get('settings',                 'Settings::index');
    $routes->post('settings/general',        'Settings::saveGeneral');
    $routes->post('settings/seo',            'Settings::saveSeo');
    $routes->post('settings/email',          'Settings::saveEmail');

    // Social
    $routes->get('social',                   'Social::index');
    $routes->post('social/store',            'Social::store');
    $routes->post('social/(:num)/delete',    'Social::delete/$1');

    // Redirects
    $routes->get('redirects',                'Redirects::index');
    $routes->post('redirects/store',         'Redirects::store');
    $routes->post('redirects/(:num)/delete', 'Redirects::delete/$1');

    // Marketplace
    $routes->get('marketplace',              'Marketplace::index');
    $routes->get('marketplace/themes',       'Marketplace::themes');
    $routes->get('marketplace/widgets',      'Marketplace::widgets');
    $routes->post('marketplace/install',     'Marketplace::install');
    $routes->post('marketplace/update/(:segment)', 'Marketplace::update/$1');

    // Store (stub)
    $routes->get('store',                    'Store::index');
});

// ===================================================
// CATCH-ALL: Static pages (must be last)
// ===================================================
$routes->get('(:segment)', 'Pages::show/$1');
