<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Backups;

use Pubvana\Plugins\Backups\Controllers\BackupsAdminController;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Backups plugin - registers the backup service and admin routes.
 *
 * @package  Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/backups');
        $config['route_prefix'] = $prefix;

        // Store the config under 'pubvana.backups' so the controller and CLI can read it
        $app->set('pubvana.backups', $config);

        // Map the backup service (singleton)
        $app->map('backups', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new BackupService(
                    $app->db(),
                    $config,
                    $app->get('database') ?? []
                );
            }
            return $instance;
        });

        $authMiddleware = null;

        $app->adext()->addRoutes('admin', [
            ['GET',  $prefix,                      [BackupsAdminController::class, 'index'],    [$authMiddleware]],
            ['POST', $prefix . '/create',          [BackupsAdminController::class, 'create'],   [$authMiddleware]],
            ['GET',  $prefix . '/status',          [BackupsAdminController::class, 'status'],   [$authMiddleware]],
            ['GET',  $prefix . '/download/@filename', [BackupsAdminController::class, 'download'], [$authMiddleware]],
            ['POST', $prefix . '/@filename/delete',   [BackupsAdminController::class, 'delete'],   [$authMiddleware]],
            ['POST', $prefix . '/restore/@filename',  [BackupsAdminController::class, 'restore'],  [$authMiddleware]],
        ], 'pubvana.backups');
    }
}