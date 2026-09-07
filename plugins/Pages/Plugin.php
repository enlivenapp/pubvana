<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages;

use Pubvana\Plugins\Pages\Controllers\PagesAdminController;
use Pubvana\Plugins\Pages\Controllers\PagesPublicController;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Pages Plugin - Registers routes and services for the pages module.
 *
 * Called by PluginLoader after pubvana.json hooks are loaded.
 * Registers admin routes via adext. Public routes use {prefix}/@slug
 * (prefix from routePrepend) to avoid catching unrelated URLs.
 *
 * @package Pubvana\Plugins\Pages
 */
class Plugin implements PluginInterface
{
    /**
     * Register the plugin's routes with adext.
     *
     * @param Engine<object> $app       Flight application
     * @param Router $router    Flight router (unused, routes go through adext)
     * @param array<string, mixed>  $config    Plugin configuration
     * @return void
     */
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/pages');
        $config['route_prefix'] = $prefix;

        $app->map('pages', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new Services\PagesService($app->db(), $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null; // Disabled for development

        // Admin routes
        $adext->addRoutes('admin', [
            ['GET',    $prefix,                      [PagesAdminController::class, 'index'],    [$authMiddleware]],
            ['GET',    $prefix . '/create',          [PagesAdminController::class, 'create'],   [$authMiddleware]],
            ['POST',   $prefix . '/store',           [PagesAdminController::class, 'store'],    [$authMiddleware]],
            ['GET',    $prefix . '/@id/edit',        [PagesAdminController::class, 'edit'],     [$authMiddleware]],
            ['POST',   $prefix . '/@id/update',      [PagesAdminController::class, 'update'],   [$authMiddleware]],
            ['POST',   $prefix . '/@id/delete',      [PagesAdminController::class, 'delete'],   [$authMiddleware]],
            ['GET',    $prefix . '/@id/revisions',   [PagesAdminController::class, 'revisions'],[$authMiddleware]],
            ['POST',   $prefix . '/@id/restore/@revisionId', [PagesAdminController::class, 'restore'], [$authMiddleware]],
        ], 'pubvana.pages');

        // Public routes
        $adext->addRoutes('public', [
            ['GET',    $prefix,             [PagesPublicController::class, 'index']],
            ['GET',    $prefix . '/@slug',  [PagesPublicController::class, 'view']],
        ], 'pubvana.pages');

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.pages', [
            'label'    => 'Pages',
            'priority' => 30,
            'callable' => fn(array $context) => $app->pages()->dashboardCards(),
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.pages', [
            'label'    => 'Pages',
            'priority' => 40,
            'callable' => fn(array $context) => $app->pages()->dashboardSections(),
        ]);

        // Quick Add linkable items for navigation manager
        $adext->register('nav.linkable', 'default', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn() => $app->pages()->navLinkableItems(),
        ]);

        // Broken Links Source
        $adext->register('brokenlinks', 'source', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => function () use ($app): array {
                $stmt = $app->db()->query(
                    "SELECT id, title, content FROM pages WHERE status = 'published' AND deleted_at IS NULL"
                );
                if ($stmt === false) {
                    return [];
                }
                $items = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'type'    => 'page',
                        'id'      => (int) $row['id'],
                        'title'   => $row['title'],
                        'content' => (string) ($row['content'] ?? ''),
                    ];
                }
                return $items;
            },
        ]);

        // Search source — pages are searchable content
        $adext->register('search', 'provider', 'pubvana.pages', [
            'label'        => 'Pages',
            'content_type' => 'Page',
            'callable'     => fn(string $term) => $app->pages()->searchProvider($term),
        ]);

        // Comments host — pages are commentable content (no per-page toggle)
        $adext->register('comments.host', 'content', 'pubvana.pages', [
            'label'    => 'Pages',
            'callable' => fn() => $app->pages()->commentHostItems(),
        ]);
    }
}
