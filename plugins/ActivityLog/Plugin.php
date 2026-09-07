<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog;

use Pubvana\Plugins\ActivityLog\Controllers\ActivityLogAdminController;
use Pubvana\Plugins\ActivityLog\Services\ActivityLogService;
use Pubvana\Services\PluginInterface;
use Enlivenapp\FlightShield\Middlewares\PermissionMiddleware;
use flight\Engine;
use flight\net\Router;

/**
 * ActivityLog Plugin - Audit trail of admin actions.
 *
 * Registers the activityLog service, admin routes under Tools,
 * auto-tracking via flight.route.executed event, and dashboard card.
 *
 * @package Pubvana\Plugins\ActivityLog
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/activity-log');
        $config['route_prefix'] = $prefix;

        // Register service facade
        $app->map('activityLog', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new ActivityLogService($app->db(), $config);
                $instance->setApp($app);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = new PermissionMiddleware($app, 'activity_log.view');

        // ─── Admin Routes (adext prepends /admin) ──────────────────────
        $adext->addRoutes('admin', [
            ['GET', $prefix, [ActivityLogAdminController::class, 'index'], [$authMiddleware]],
        ], 'pubvana.activity-log');

        // ─── Dashboard Card ─────────────────────────────────────────────
        $adext->register('admin.dashboard', 'cards', 'pubvana.activity-log', [
            'label'    => 'Recent Admin Activity',
            'priority' => 25,
            'callable' => function (array $context) use ($app, $prefix): array {
                $count = $app->activityLog()->countRecent24h();
                return [
                    [
                        'id'          => 'recent-activity',
                        'label'       => 'Activity (24h)',
                        'value'       => $count,
                        'icon'        => 'ti-activity',
                        'tone'        => $count > 0 ? 'info' : 'secondary',
                        'group'       => 'tools',
                        'href'        => $prefix,
                        'description' => $count > 0
                            ? "{$count} admin actions in the last 24 hours."
                            : 'No admin activity in the last 24 hours.',
                    ],
                ];
            },
        ]);

        // ─── Auto-tracking via flight.route.executed ────────────────────
        // Only fires when a route actually dispatches successfully
        $app->onEvent('flight.route.executed', function ($route, $executionTime) use ($app, $prefix) {
            // Check config flag
            if ($app->get('activity_log.track_admin_actions') === false) {
                return;
            }

            // Only admin routes with mutating methods. Route has no $method
            // property, so read the actual request method.
            $pattern = $route->pattern ?? '';
            $method = $app->request()->method;

            if (!str_starts_with($pattern, '/admin/')) {
                return;
            }

            if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                return;
            }

            // Skip certain routes
            $skipPatterns = [
                '/admin/auth/',
                '/admin/assets/',
                '/admin/api/',
                '/admin' . $prefix,
            ];

            foreach ($skipPatterns as $skip) {
                if (str_starts_with($pattern, $skip)) {
                    return;
                }
            }

            $app->activityLog()->logFromRoute($route);
        });
    }
}