<?php

namespace App\Services;

class MarketplaceService
{
    protected string $apiBase = 'https://pubvana.net/api/marketplace';
    protected int    $cacheTtl = 3600; // 1 hour

    protected int    $revalidateDays = 90;
    protected int    $dailyCheckTtl  = 86400;   // 1 day
    protected string $dailyCacheKey  = 'license_due_check';

    private function isDevDomain(): bool
    {
        $host = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        return $host === 'localhost' || str_ends_with($host, '.local');
    }

    /**
     * Fetch items from the live API, with 1-hour cache and mock fallback.
     */
    protected function fetchFromApi(string $type = ''): array
    {
        $cacheKey = 'marketplace_api_' . ($type ?: 'all');
        $cached   = cache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $url = $this->apiBase . '/items' . ($type ? '?type=' . $type : '');

        try {
            $client   = \Config\Services::curlrequest(['timeout' => 5]);
            $response = $client->get($url, ['http_errors' => false]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                if (is_array($data)) {
                    cache()->save($cacheKey, $data, $this->cacheTtl);
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'MarketplaceService API unreachable: ' . $e->getMessage());
        }

        // Fallback to mock data
        return $type === 'theme' ? $this->mockThemes()
             : ($type === 'widget' ? $this->mockWidgets()
             : array_merge($this->mockThemes(), $this->mockWidgets()));
    }

    /**
     * Bust all marketplace API caches.
     */
    public function refreshCache(): void
    {
        cache()->delete('marketplace_api_all');
        cache()->delete('marketplace_api_theme');
        cache()->delete('marketplace_api_widget');
    }


    protected function mockThemes(): array
    {
        return [
            [
                'type'           => 'theme',
                'name'           => 'Ember',
                'slug'           => 'ember',
                'description'    => 'A warm, modern theme with amber accents. Inter + Lora typography, hero homepage, reading-time badges, author cards, and full sidebar support.',
                'version'        => '1.0.0',
                'price'          => 29.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/themes/ember',
                'screenshot_url' => 'https://pubvana.net/screenshots/ember.png',
                'author'         => 'Pubvana Team',
            ],
        ];
    }

    protected function mockWidgets(): array
    {
        return [];
    }

    public function fetchThemes(): array
    {
        return array_map([$this, 'normalizeItem'], $this->fetchFromApi('theme'));
    }

    public function fetchWidgets(): array
    {
        return array_map([$this, 'normalizeItem'], $this->fetchFromApi('widget'));
    }

    public function fetchAll(): array
    {
        return array_map([$this, 'normalizeItem'], $this->fetchFromApi());
    }

    /**
     * Normalise a raw item array (from API or mock) into a consistent stdClass.
     * The live API uses item_type (DB column name); mock data uses type.
     * Ensures both ->type and ->item_type are always set.
     */
    private function normalizeItem(array $item): object
    {
        // Unify type field: prefer item_type from API, fall back to type from mock
        if (! isset($item['type']) && isset($item['item_type'])) {
            $item['type'] = $item['item_type'];
        } elseif (! isset($item['item_type']) && isset($item['type'])) {
            $item['item_type'] = $item['type'];
        }
        $item['installed_version'] = $item['installed_version'] ?? null;
        return (object) $item;
    }

    public function installFree(string $downloadUrl, string $type, string $folder): bool
    {
        // Only allow downloads from pubvana.net
        $parsed = parse_url($downloadUrl);
        $host   = strtolower($parsed['host'] ?? '');
        if ($host !== 'pubvana.net' && ! str_ends_with($host, '.pubvana.net')) {
            log_message('warning', 'MarketplaceService: rejected download URL not from pubvana.net: ' . $downloadUrl);
            return false;
        }

        // Folder name must be safe (no path traversal)
        if (! preg_match('/^[a-z0-9_-]+$/', $folder)) {
            return false;
        }

        $tmpDir  = WRITEPATH . 'tmp/';
        $zipPath = $tmpDir . $folder . '.zip';
        $destDir = ($type === 'theme') ? THEMES_PATH : WIDGETS_PATH;

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zip = @file_get_contents($downloadUrl);
        if ($zip === false) {
            return false;
        }
        file_put_contents($zipPath, $zip);

        $archive = new \ZipArchive();
        if ($archive->open($zipPath) !== true) {
            @unlink($zipPath);
            return false;
        }

        // Reject ZIPs containing path traversal entries
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $entry = $archive->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                $archive->close();
                @unlink($zipPath);
                log_message('warning', 'MarketplaceService: ZIP entry contains path traversal: ' . $entry);
                return false;
            }
        }

        $archive->extractTo($destDir);
        $archive->close();
        @unlink($zipPath);

        if ($type === 'theme') {
            (new ThemeService())->symlinkAssets($folder);
        }

        $this->registerInstalled($type, $folder);
        return true;
    }

    protected function registerInstalled(string $type, string $folder): void
    {
        $dir      = ($type === 'theme') ? THEMES_PATH : WIDGETS_PATH;
        $infoFile = $dir . $folder . '/' . ($type === 'theme' ? 'theme_info' : 'widget_info') . '.php';
        $info     = is_file($infoFile) ? require $infoFile : [];

        $table = ($type === 'theme') ? 'themes' : 'widgets';
        $db    = db_connect();
        $now   = date('Y-m-d H:i:s');

        $existing = $db->table($table)->where('folder', $folder)->get()->getRowObject();
        if ($existing) {
            $db->table($table)->where('folder', $folder)->update(['version' => $info['version'] ?? null, 'updated_at' => $now]);
        } else {
            $db->table($table)->insert([
                'name'       => $info['name'] ?? $folder,
                'folder'     => $folder,
                'version'    => $info['version'] ?? null,
                'is_active'  => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Validate a license key via the pubvana.net API and install the item.
     */
    public function installLicensed(string $licenseKey, string $slug, string $type): bool
    {
        $client = \Config\Services::curlrequest(['timeout' => 10]);

        try {
            $response = $client->post('https://pubvana.net/api/license/validate', [
                'json' => [
                    'license_key' => $licenseKey,
                    'domain'      => base_url(),
                    'item_slug'   => $slug,
                ],
                'http_errors' => false,
            ]);

            $body   = json_decode($response->getBody(), true);
            $status = $response->getStatusCode();

            if ($status !== 200 || empty($body['valid'])) {
                $error = $body['error'] ?? 'License validation failed (HTTP ' . $status . ')';
                log_message('warning', 'MarketplaceService::installLicensed failed: ' . $error);
                return false;
            }

            $downloadUrl = $body['download_url'] ?? null;
            if (! $downloadUrl) {
                log_message('warning', 'MarketplaceService::installLicensed: no download_url in response');
                return false;
            }

            $installed = $this->installFree($downloadUrl, $type, $slug);
            if (! $installed) {
                return false;
            }

            // Persist the license key and initial validation state
            db_connect()->table('marketplace_items')
                ->where('slug', $slug)
                ->update([
                    'license_key'          => $licenseKey,
                    'license_last_checked' => date('Y-m-d H:i:s'),
                    'license_valid'        => 1,
                ]);

            return true;

        } catch (\Throwable $e) {
            log_message('error', 'MarketplaceService::installLicensed exception: ' . $e->getMessage());
            return false;
        }
    }

    public function checkUpdates(): array
    {
        $db = db_connect();
        return $db->table('marketplace_items')
            ->where('installed_version IS NOT NULL')
            ->where('installed_version != version')
            ->get()->getResultArray();
    }

    /**
     * Re-validate all (or overdue) licensed items against the pubvana.net API.
     *
     * Returns an array of per-item status records:
     *   ['slug' => string, 'status' => 'valid'|'invalid'|'unreachable'|'skipped']
     */
    public function revalidateLicenses(bool $force = false): array
    {
        if ($this->isDevDomain()) {
            return [];
        }

        $db    = db_connect();
        $rows  = $db->table('marketplace_items')
            ->whereNotNull('license_key')
            ->where('license_key !=', '')
            ->get()->getResult();

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $this->revalidateDays . ' days'));
        $client = \Config\Services::curlrequest(['timeout' => 10]);
        $results = [];

        foreach ($rows as $item) {
            // Skip items that are not yet overdue (unless force mode)
            if (! $force && $item->license_last_checked !== null && $item->license_last_checked > $cutoff) {
                $results[] = ['slug' => $item->slug, 'status' => 'skipped'];
                continue;
            }

            try {
                $response = $client->post('https://pubvana.net/api/license/validate', [
                    'json' => [
                        'license_key' => $item->license_key,
                        'domain'      => base_url(),
                        'item_slug'   => $item->slug,
                    ],
                    'http_errors' => false,
                ]);

                $status = $response->getStatusCode();
                $body   = json_decode($response->getBody(), true);

                if ($status === 200 && isset($body['valid'])) {
                    $valid = (bool) $body['valid'];
                    $db->table('marketplace_items')->where('slug', $item->slug)->update([
                        'license_last_checked' => date('Y-m-d H:i:s'),
                        'license_valid'        => $valid ? 1 : 0,
                    ]);
                    $results[] = ['slug' => $item->slug, 'status' => $valid ? 'valid' : 'invalid'];
                } else {
                    // Non-200 or malformed — reset clock but leave license_valid unchanged
                    $db->table('marketplace_items')->where('slug', $item->slug)->update([
                        'license_last_checked' => date('Y-m-d H:i:s'),
                    ]);
                    $results[] = ['slug' => $item->slug, 'status' => 'unreachable'];
                }
            } catch (\Throwable $e) {
                log_message('warning', 'MarketplaceService::revalidateLicenses unreachable for ' . $item->slug . ': ' . $e->getMessage());
                $results[] = ['slug' => $item->slug, 'status' => 'unreachable'];
            }
        }

        return $results;
    }

    /**
     * Called on every request (from BaseController). Uses a daily cache gate so
     * actual DB/API work happens at most once per day.
     */
    public function checkAndRevalidateIfDue(): void
    {
        if ($this->isDevDomain()) {
            return;
        }

        if (cache($this->dailyCacheKey) !== null) {
            return;
        }

        // Set the cache key first to prevent concurrent requests double-firing
        cache()->save($this->dailyCacheKey, true, $this->dailyCheckTtl);

        $this->revalidateLicenses(false);
    }
}
