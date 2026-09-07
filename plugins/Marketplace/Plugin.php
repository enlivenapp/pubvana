<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Marketplace;

use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use Pubvana\Plugins\Marketplace\Controllers\MarketplaceAdminController;
use Pubvana\Plugins\Marketplace\Services\MarketplaceService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Marketplace Plugin - the companion app for the Pubvana Digital Store.
 *
 * Registers the `marketplace` singleton, the Tools > Marketplace admin, and
 * the 24h cron task that performs the ~2 week purchase/license verification
 * (phone-home) at pubvanacms.com.
 *
 * @package Pubvana\Plugins\Marketplace
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/marketplace');
        $config['route_prefix'] = $prefix;

        $app->map('marketplace', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new MarketplaceService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $prefix = $app->pluginLoader()->routePrefix('pubvana/marketplace');
        $manage = new PermissionMiddleware($app, 'marketplace.manage');

        // Admin routes (adext prepends /admin)
        $adext->addRoutes('admin', [
            ['GET',  $prefix,                              [MarketplaceAdminController::class, 'index'],        [$manage]],
            ['POST', $prefix . '/connect',                 [MarketplaceAdminController::class, 'connect'],     [$manage]],
            ['POST', $prefix . '/disconnect',              [MarketplaceAdminController::class, 'disconnect'],  [$manage]],
            ['GET',  $prefix . '/purchases',               [MarketplaceAdminController::class, 'purchases'],   [$manage]],
            ['POST', $prefix . '/verify',                  [MarketplaceAdminController::class, 'verify'],      [$manage]],
            ['POST', $prefix . '/cart-add',                [MarketplaceAdminController::class, 'addToCart'],   [$manage]],
            ['POST', $prefix . '/install',                 [MarketplaceAdminController::class, 'install'],     [$manage]],
            ['POST', $prefix . '/reinstall',               [MarketplaceAdminController::class, 'reinstallAll'], [$manage]],
            ['GET',  $prefix . '/cart-open',               [MarketplaceAdminController::class, 'cartOpen'],    [$manage]],
        ], 'pubvana.marketplace');

        // Core cron system (docs/Cron.md): the 24h task enforces the
        // verify_days cadence internally, so the store is actually phoned
        // home about every two weeks. Real failures throw (CronService logs
        // FAILED and exits 2); graceful "not due / nothing to do" stays quiet.
        $adext->register('cron', '24h', 'pubvana.marketplace', [
            'label'    => 'Marketplace purchase verification (phone-home)',
            'priority' => 50,
            'callable' => function () use ($app): void {
                $app->marketplace()->verifyIfDue();
            },
        ]);
    }
}