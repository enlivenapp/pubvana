<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth;

use Pubvana\Plugins\SiteHealth\Controllers\HealthAdminController;
use Pubvana\Plugins\SiteHealth\Services\HealthService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Site Health Plugin - Configuration, security, environment and plugin checks.
 *
 * Registers the health service, the admin dashboard under Tools, and a
 * dashboard card that appears only when issues exist.
 *
 * @package Pubvana\Plugins\SiteHealth
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/sitehealth');
        $config['route_prefix'] = $prefix;

        $app->map('health', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new HealthService($app, $app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  $prefix,              [HealthAdminController::class, 'index'], [$authMiddleware]],
            ['POST', $prefix . '/rerun',   [HealthAdminController::class, 'rerun'], [$authMiddleware]],
        ], 'pubvana.sitehealth');

        // ─── Dashboard ──────────────────────────────────────────────────
        // Card renders only when the health service reports issues.

        $adext->register('admin.dashboard', 'cards', 'pubvana.sitehealth', [
            'label'    => 'Site Health',
            'priority' => 40,
            'callable' => function (array $context) use ($app): array {
                return $app->health()->dashboardCards();
            },
        ]);
    }
}
