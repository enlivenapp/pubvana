<?php

declare(strict_types=1);

namespace Pubvana\Plugins\SiteHealth\Services;

use flight\Engine;
use Pubvana\Plugins\SiteHealth\Interfaces\CheckInterface;

class HealthService
{
    private string $cachePath;
    private int $cacheTtl;

    /** @var CheckInterface[] */
    private array $additionalChecks = [];

    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
    public function __construct(
        private Engine $app,
        private \PDO $pdo,
        private array $config = [],
    ) {
        $projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 5);
        $this->cachePath = $projectRoot . '/writable/cache/sitehealth.json';
        $this->cacheTtl = (int) ($config['cache_ttl'] ?? 3600);
    }

    public function addCheck(CheckInterface $check): void
    {
        $this->additionalChecks[] = $check;
    }

    /**
     * Public route prefix for this plugin (no '/admin'; the dashboard
     * renderer adds it). Falls back to the plugin id's default.
     */
    private function routePrefix(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }

    /**
     * Run all checks (or return cached results if still valid).
     *
     * @param bool $force Bypass cache
     * @return array<string, mixed>
     */
    public function runAll(bool $force = false): array
    {
        if (!$force) {
            $cached = $this->loadCache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $results = [];
        foreach ($this->getChecks() as $check) {
            $results[] = $check->run()->toArray();
        }

        // Collect external checks registered via adext (type 'health')
        $external = $this->app->adext()->get('health', 'checks');
        foreach ($external as $contribution) {
            if (isset($contribution['callable']) && is_callable($contribution['callable'])) {
                $result = call_user_func($contribution['callable']);
                if ($result instanceof CheckResult) {
                    $results[] = $result->toArray();
                } elseif (is_array($result) && isset($result['id'])) {
                    $results[] = $result;
                }
            }
        }

        $summary = $this->summarize($results);
        $data = [
            'results'   => $results,
            'summary'   => $summary,
            'cached_at' => date('Y-m-d H:i:s'),
        ];

        $this->saveCache($data);
        return $data;
    }

    /**
     * Dashboard card data. Returns empty array when no issues (card won't render).
     *
     * @return array<int, array<string, string>>
     */
    public function dashboardCards(): array
    {
        $data = $this->runAll();
        $summary = $data['summary'];

        if ($summary['critical'] === 0 && $summary['warning'] === 0) {
            return [];
        }

        $cards = [];

        if ($summary['critical'] > 0) {
            $cards[] = [
                'id'          => 'health-critical',
                'label'       => 'Site Health',
                'value'       => $summary['critical'] . ' Critical',
                'icon'        => 'ti-alert-circle',
                'tone'        => 'danger',
                'group'       => 'tools',
                'href'        => $this->routePrefix(),
                'description' => $summary['critical'] . ' critical issue(s) need immediate attention.',
            ];
        } elseif ($summary['warning'] > 0) {
            $cards[] = [
                'id'          => 'health-warnings',
                'label'       => 'Site Health',
                'value'       => $summary['warning'] . ' Warning(s)',
                'icon'        => 'ti-alert-triangle',
                'tone'        => 'warning',
                'group'       => 'tools',
                'href'        => $this->routePrefix(),
                'description' => $summary['warning'] . ' item(s) could be improved.',
            ];
        }

        return $cards;
    }

    /**
     * Categorize results by category.
     *
     * @param array<int, array<string, mixed>> $results
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupByCategory(array $results): array
    {
        $grouped = [];
        foreach ($results as $result) {
            $cat = $result['category'] ?? 'other';
            $grouped[$cat][] = $result;
        }
        return $grouped;
    }

    /**
     * Clear cached results.
     */
    public function clearCache(): void
    {
        if (file_exists($this->cachePath)) {
            @unlink($this->cachePath);
        }
    }

    /**
     * @return CheckInterface[]
     */
    private function getChecks(): array
    {
        $projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 5);
        $migrationConfig = (array) ($this->app->get('migrations') ?? []);

        $checks = [
            // Environment
            new PhpVersionCheck(),
            new PhpExtensionsCheck(),
            new DatabaseCheck($this->pdo),
            new DiskSpaceCheck($projectRoot . '/public/uploads'),

            // Security
            new HttpsCheck($this->app),
            new DebugModeCheck($this->app),
            new EnvironmentFilePermissionsCheck($projectRoot . '/.env'),
            new ShieldCheck($this->app),
            new SessionConfigCheck($this->app),

            // Configuration
            new RequiredSettingsCheck($this->app),
            new WritableDirectoriesCheck($projectRoot),
            new ConfigDefaultsCheck($projectRoot),

            // Plugins
            new PluginMigrationsCheck($this->pdo, $migrationConfig),
            new PluginDependenciesCheck($projectRoot),
        ];

        return array_merge($checks, $this->additionalChecks);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @return array<string, int|string>
     */
    private function summarize(array $results): array
    {
        $summary = ['critical' => 0, 'warning' => 0, 'pass' => 0, 'total' => count($results)];

        foreach ($results as $result) {
            $status = $result['status'] ?? 'pass';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        if ($summary['critical'] > 0) {
            $summary['overall'] = 'critical';
        } elseif ($summary['warning'] > 0) {
            $summary['overall'] = 'warning';
        } else {
            $summary['overall'] = 'good';
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCache(): ?array
    {
        if (!file_exists($this->cachePath)) {
            return null;
        }

        $content = (string) file_get_contents($this->cachePath);
        $data = json_decode($content, true);

        if (!is_array($data) || empty($data['cached_at'])) {
            return null;
        }

        $cachedTime = strtotime($data['cached_at']);
        if ($cachedTime === false || (time() - $cachedTime) > $this->cacheTtl) {
            return null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveCache(array $data): void
    {
        $dir = dirname($this->cachePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($this->cachePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
