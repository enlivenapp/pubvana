<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates;

use Pubvana\Plugins\SiteHealth\Services\CheckResult;
use Pubvana\Plugins\Updates\Controllers\UpdatesAdminController;
use Pubvana\Plugins\Updates\Services\UpdateService;
use Pubvana\Services\PluginInterface;
use flight\Engine;
use flight\net\Router;

/**
 * Updates plugin - release checks and core updates for Pubvana.
 *
 * Registers the updates service facade, the admin screen under Tools,
 * a dashboard card in the system group, and a Site Health check that
 * reports the local update state (never the network).
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class Plugin implements PluginInterface
{
    public function register(Engine $app, Router $router, array $config = []): void
    {
        $prefix = $app->pluginLoader()->routePrefix('pubvana/updates');
        $config['route_prefix'] = $prefix;

        $app->set('pubvana.updates', $config);

        $app->map('updates', function () use ($app, $config) {
            static $instance = null;
            if ($instance === null) {
                $instance = new UpdateService($app, $config);
            }
            return $instance;
        });

        $adext = $app->adext();
        $authMiddleware = null;

        $adext->addRoutes('admin', [
            ['GET',  $prefix,                  [UpdatesAdminController::class, 'index'],    [$authMiddleware]],
            ['POST', $prefix . '/check',       [UpdatesAdminController::class, 'check'],    [$authMiddleware]],
            ['POST', $prefix . '/apply',       [UpdatesAdminController::class, 'apply'],    [$authMiddleware]],
            ['GET',  $prefix . '/status',      [UpdatesAdminController::class, 'status'],   [$authMiddleware]],
            ['POST', $prefix . '/settings',    [UpdatesAdminController::class, 'settings'], [$authMiddleware]],
            ['POST', $prefix . '/skip',        [UpdatesAdminController::class, 'skip'],     [$authMiddleware]],
            ['POST', $prefix . '/unskip',      [UpdatesAdminController::class, 'unskip'],   [$authMiddleware]],
        ], 'pubvana.updates');

        $adext->register('admin.dashboard', 'cards', 'pubvana.updates', [
            'label'    => 'Updates',
            'priority' => 5,
            'callable' => fn(array $context): array => $this->dashboardCards($app, $prefix),
        ]);

        // Site Health check. Only registered when SiteHealth is present;
        // the check reads the cached local state, never the network.
        if (class_exists(CheckResult::class)) {
            $adext->register('health', 'checks', 'pubvana.updates', [
                'label'    => 'Updates',
                'priority' => 50,
                'callable' => fn(): CheckResult => $this->healthCheck($app),
            ]);
        }

        // Core cron system (docs/Cron.md): daily task runs the auto-update
        // chain. Only real failures throw, so CronService logs FAILED and
        // exits 2; "nothing to do" outcomes stay quiet.
        $adext->register('cron', '24h', 'pubvana.updates', [
            'label'    => 'Pubvana updates (auto-update chain)',
            'priority' => 50,
            'callable' => function () use ($app): void {
                $result = $app->updates()->runAutoUpdateChain();
                if ($result['status'] === 'error') {
                    throw new \RuntimeException($result['message']);
                }
            },
        ]);
    }

    /**
     * Dashboard card: installed version, plus update availability from
     * the cached check state (no network on dashboard renders).
     *
     * @param Engine<object> $app
     * @param string         $prefix Route prefix for this plugin; the
     *                               dashboard renderer adds '/admin' to hrefs itself
     * @return list<array<string, mixed>>
     */
    private function dashboardCards(Engine $app, string $prefix): array
    {
        $service = $app->updates();
        $state   = $service->lastCheck();
        $version = (string) ($state['current_version'] ?? $service->currentVersion());

        $card = [
            'id'          => 'pubvana-version',
            'label'       => 'Pubvana Version',
            'value'       => $version,
            'icon'        => 'ti-refresh',
            'tone'        => 'success',
            'group'       => 'system',
            'href'        => $prefix,
            'description' => 'Running the latest version.',
        ];

        if (($state['status'] ?? '') === 'available' && isset($state['target_version'])) {
            $card['tone']        = 'warning';
            $card['description'] = 'Version ' . $state['target_version'] . ' is available. Visit Tools > Updates.';
        } elseif (($state['status'] ?? '') === 'error') {
            $card['tone']        = 'secondary';
            $card['description'] = 'Last update check failed. Visit Tools > Updates.';
        }

        return [$card];
    }

    /**
     * Site Health contribution built from the cached check state.
     *
     * @param Engine<object> $app
     */
    private function healthCheck(Engine $app): CheckResult
    {
        $state = $app->updates()->lastCheck();
        $version = (string) ($state['current_version'] ?? 'unknown');

        return match ((string) ($state['status'] ?? 'unknown')) {
            'up_to_date' => new CheckResult(
                id: 'pubvana-update',
                name: 'Pubvana Updates',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::PASS,
                message: 'Pubvana ' . $version . ' is up to date.'
            ),
            'available' => new CheckResult(
                id: 'pubvana-update',
                name: 'Pubvana Updates',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'Version ' . ($state['target_version'] ?? 'newer') . ' is available.',
                remediation: 'Apply it from Tools > Updates. A pre-update backup is taken automatically.'
            ),
            default => new CheckResult(
                id: 'pubvana-update',
                name: 'Pubvana Updates',
                category: CheckResult::CAT_PLUGINS,
                status: CheckResult::WARNING,
                message: 'No recent update check.',
                remediation: 'Visit Tools > Updates and run a check.'
            ),
        };
    }
}
