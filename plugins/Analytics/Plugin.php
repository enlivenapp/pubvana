<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Analytics;

use Pubvana\Plugins\Analytics\Controllers\AnalyticsAdminController;
use Pubvana\Plugins\Analytics\Services\AnalyticsService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Analytics Plugin - Server-side site traffic tracking and reporting.
 *
 * Registers the analytics service, the admin report under Tools, and a
 * runtime listener on the flight.route.executed event that records a
 * page view for every successfully dispatched public request.
 *
 * @package Pubvana\Plugins\Analytics
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/analytics');
        $config['route_prefix'] = $prefix;

        $app->map('analytics', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new AnalyticsService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  $prefix,                [AnalyticsAdminController::class, 'index'],          [$authMiddleware]],
            ['GET',  $prefix . '/data',      [AnalyticsAdminController::class, 'data'],           [$authMiddleware]],
            ['POST', $prefix . '/tracking',  [AnalyticsAdminController::class, 'toggleTracking'], [$authMiddleware]],
        ], 'pubvana.analytics');

        // ─── Page View Tracking ────────────────────────────────────────
        // Fires only when a route actually dispatched, so 404s and static
        // files are never counted. logView() guards every case and never
        // throws. The daily rollup also keys off the first hit of the day.

        $app->onEvent('flight.route.executed', function () use ($app) {
            $app->analytics()->logView();
            $app->analytics()->maybeRollup();
        });

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.analytics', [
            'label'    => 'Analytics',
            'priority' => 30,
            'callable' => function (array $context) use ($app, $prefix): array {
                $views = $app->analytics()->totalViews('7');

                return [[
                    'id'          => 'views-last-7-days',
                    'label'       => 'Views (7 days)',
                    'value'       => $views,
                    'icon'        => 'ti-eye',
                    'tone'        => $views > 0 ? 'success' : 'secondary',
                    'group'       => 'analytics',
                    'href'        => $prefix,
                    'description' => 'Page views recorded in the last 7 days.',
                ]];
            },
        ]);
    }
}