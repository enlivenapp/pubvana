# Pubvana Plugin System

Drop a folder here and Pubvana will discover it automatically on every request.

## Directory Structure

```
plugins/
  ecommerce/
    Plugin.php          ← required entry point
    Config/
      Routes.php        ← optional extra routes
    Controllers/
    Models/
    Views/
    ...
```

## Creating a Plugin

### 1. `Plugin.php`

Your plugin file must define a class named `{FolderName}Plugin` (e.g. folder `ecommerce` → class `EcommercePlugin`) that implements `App\Interfaces\PluginInterface`:

```php
<?php

use App\Interfaces\PluginInterface;

class EcommercePlugin implements PluginInterface
{
    public function getName(): string    { return 'E-commerce'; }
    public function getSlug(): string    { return 'ecommerce'; }
    public function getVersion(): string { return '1.0.0'; }

    public function getMenuItems(): array
    {
        return [
            ['label' => 'Products',  'url' => '/admin/products',  'icon' => 'fa-box'],
            ['label' => 'Orders',    'url' => '/admin/orders',    'icon' => 'fa-shopping-cart'],
        ];
    }

    public function register(): void
    {
        // Hook into CI4 Events, bind services, etc.
    }
}
```

### 2. `Config/Routes.php` (optional)

If your plugin needs its own routes, place them in `plugins/ecommerce/Config/Routes.php`.
They are auto-included by `app/Config/Routes.php` after all core routes.

```php
<?php
$routes->group('admin', ['filter' => 'admin_auth', 'namespace' => 'EcommercePlugin\Controllers'], function ($routes) {
    $routes->get('products',       'Products::index');
    $routes->get('products/create','Products::create');
    // ...
});
```

## How Discovery Works

1. **Routes** — `app/Config/Routes.php` globs `plugins/*/Config/Routes.php` and `require`s each file.
2. **PluginManager** — `App\Services\PluginManager::instance()->loadAll()` is called in `BaseController::initController()`. It globs `plugins/*/Plugin.php`, `require_once`s each file, instantiates the class, and calls `register()`.
3. **Sidebar** — `$plugin_menu_items` is injected into all admin views. The sidebar renders a "Plugins" section automatically when at least one plugin provides menu items.

## Namespacing

Plugins are not in the `App\` namespace. Use your own namespace (e.g. `EcommercePlugin\`) and configure autoloading in `app/Config/Autoload.php` if needed:

```php
public $psr4 = [
    APP_NAMESPACE    => APPPATH,
    'EcommercePlugin' => ROOTPATH . 'plugins/ecommerce',
];
```
