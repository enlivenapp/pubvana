<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles;

use Pubvana\Plugins\Profiles\Controllers\ProfilesAdminController;
use Pubvana\Plugins\Profiles\Controllers\ProfilesPublicController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/profiles');
        $config['route_prefix'] = $prefix;

        $app->map('profiles', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Models\Profile($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // Admin routes
        $adext->addRoutes('admin', [
            ['GET',  $prefix,                  [ProfilesAdminController::class, 'index'],  [$authMiddleware]],
            ['GET',  $prefix . '/@userId',     [ProfilesAdminController::class, 'show'],   [$authMiddleware]],
            ['POST', $prefix . '/@userId/update', [ProfilesAdminController::class, 'update'], [$authMiddleware]],
        ], 'pubvana.profiles');

        // Public routes
        $adext->addRoutes('public', [
            ['GET',  $prefix . '/@username',       [ProfilesPublicController::class, 'show']],
            ['GET',  $prefix . '/@username/edit',  [ProfilesPublicController::class, 'edit']],
            ['POST', $prefix . '/@username/update', [ProfilesPublicController::class, 'update']],
        ], 'pubvana.profiles');

        // Public CSS
        $adext->register('public.css', 'default', 'pubvana.profiles', [
            'url'      => '/assets/plugin/Profiles/css/profiles.css',
            'priority' => 50,
        ]);
    }
}
