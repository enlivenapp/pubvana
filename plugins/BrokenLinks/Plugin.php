<?php

declare(strict_types=1);

namespace Pubvana\Plugins\BrokenLinks;

use Pubvana\Plugins\BrokenLinks\Controllers\BrokenLinksAdminController;
use Pubvana\Plugins\BrokenLinks\Services\BrokenLinksService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Broken Links Plugin - Scans outbound links in content and reports broken URLs.
 *
 * Registers the broken links service, the admin screen under Tools,
 * dashboard contributions, and a content-source extension point
 * (brokenlinks.source) that other plugins register into.
 *
 * @package Pubvana\Plugins\BrokenLinks
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/brokenlinks');
        $config['route_prefix'] = $prefix;

        $app->map('brokenLinks', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new BrokenLinksService($app->db(), $app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        // ─── Admin Routes ──────────────────────────────────────────────

        $adext->addRoutes('admin', [
            ['GET',  $prefix,                        [BrokenLinksAdminController::class, 'index'],   [$authMiddleware]],
            ['POST', $prefix . '/scan',              [BrokenLinksAdminController::class, 'scan'],    [$authMiddleware]],
            ['POST', $prefix . '/@id/recheck',       [BrokenLinksAdminController::class, 'recheck'], [$authMiddleware]],
            ['POST', $prefix . '/@id/dismiss',       [BrokenLinksAdminController::class, 'dismiss'], [$authMiddleware]],
        ], 'pubvana.brokenlinks');

        // Core cron system (docs/Cron.md): daily scan of all registered
        // outbound links. The scan never throws; broken-link findings are
        // informational and surfaced on the admin screen, so the task stays
        // quiet in the cron log unless a real (uncaught) failure occurs.
        $adext->register('cron', '24h', 'pubvana.brokenlinks', [
            'label'    => 'Scan for broken links',
            'priority' => 60,
            'callable' => function () use ($app): void {
                $app->brokenLinks()->scan();
            },
        ]);

        // ─── Dashboard ──────────────────────────────────────────────────

        $adext->register('admin.dashboard', 'cards', 'pubvana.brokenlinks', [
            'label'    => 'Broken Links',
            'priority' => 45,
            'callable' => function (array $context) use ($app, $prefix): array {
                $count = $app->brokenLinks()->countBroken();

                return [
                    [
                        'id'          => 'broken-links',
                        'label'       => 'Broken Links',
                        'value'       => $count,
                        'icon'        => 'ti-link-off',
                        'tone'        => $count > 0 ? 'danger' : 'success',
                        'group'       => 'tools',
                        'href'        => $prefix,
                        'description' => $count > 0
                            ? 'Outbound broken links needing attention.'
                            : 'No broken links detected.',
                    ],
                ];
            },
        ]);

        $adext->register('admin.dashboard', 'sections', 'pubvana.brokenlinks', [
            'label'    => 'Broken Links',
            'priority' => 20,
            'callable' => function (array $context) use ($app, $prefix): array {
                $items = [];
                foreach ($app->brokenLinks()->recent(5) as $entry) {
                    $lastChecked = strtotime((string) $entry->last_checked_at);
                    $lastChecked = $lastChecked === false ? time() : $lastChecked;
                    $items[] = [
                        'label'    => $entry->url,
                        'meta'     => ($entry->source_type === 'post' ? 'Post' : 'Page')
                            . ': ' . $entry->source_title
                            . ' - Last checked ' . date('M j, Y g:ia', $lastChecked),
                        'href'     => $prefix,
                        'emphasis' => 'danger',
                    ];
                }

                return [[
                    'id'          => 'recent-broken-links',
                    'title'       => 'Recent Broken Links',
                    'type'        => 'list',
                    'icon'        => 'ti-link-off',
                    'tone'        => 'danger',
                    'group'       => 'tools',
                    'href'        => $prefix,
                    'empty_state' => 'No broken links detected.',
                    'items'       => $items,
                ]];
            },
        ]);
    }
}
