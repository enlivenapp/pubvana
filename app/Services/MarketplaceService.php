<?php

namespace App\Services;

class MarketplaceService
{
    protected string $apiBase = 'https://pubvana.net/api/marketplace';
    protected int    $cacheTtl = 3600; // 1 hour

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
                'item_type'      => 'theme',
                'name'           => 'Aurora',
                'slug'           => 'aurora',
                'description'    => 'A clean, modern Bootstrap 5 theme for blogs.',
                'version'        => '1.2.0',
                'price'          => 0.00,
                'is_free'        => true,
                'download_url'   => 'https://pubvana.net/marketplace/download/aurora',
                'store_url'      => null,
                'screenshot_url' => 'https://pubvana.net/marketplace/screenshots/aurora.png',
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'theme',
                'name'           => 'Nightfall',
                'slug'           => 'nightfall',
                'description'    => 'Dark mode magazine theme with featured hero.',
                'version'        => '2.0.1',
                'price'          => 29.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/themes/nightfall',
                'screenshot_url' => 'https://pubvana.net/marketplace/screenshots/nightfall.png',
                'author'         => 'ThemeForge',
            ],
            [
                'item_type'      => 'theme',
                'name'           => 'Minimal',
                'slug'           => 'minimal',
                'description'    => 'A minimal, typography-focused free theme.',
                'version'        => '1.0.0',
                'price'          => 0.00,
                'is_free'        => true,
                'download_url'   => 'https://pubvana.net/marketplace/download/minimal',
                'store_url'      => null,
                'screenshot_url' => 'https://pubvana.net/marketplace/screenshots/minimal.png',
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'theme',
                'name'           => 'Portfolio Pro',
                'slug'           => 'portfolio-pro',
                'description'    => 'Premium portfolio + blog theme with full-screen hero.',
                'version'        => '3.1.0',
                'price'          => 49.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/themes/portfolio-pro',
                'screenshot_url' => 'https://pubvana.net/marketplace/screenshots/portfolio-pro.png',
                'author'         => 'PixelCraft',
            ],
        ];
    }

    protected function mockWidgets(): array
    {
        return [
            [
                'item_type'      => 'widget',
                'name'           => 'Instagram Feed',
                'slug'           => 'instagram-feed',
                'description'    => 'Display your latest Instagram photos.',
                'version'        => '1.3.0',
                'price'          => 0.00,
                'is_free'        => true,
                'download_url'   => 'https://pubvana.net/marketplace/download/instagram-feed',
                'store_url'      => null,
                'screenshot_url' => null,
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'widget',
                'name'           => 'Newsletter Pro',
                'slug'           => 'newsletter-pro',
                'description'    => 'Mailchimp & ConvertKit integration widget.',
                'version'        => '2.0.0',
                'price'          => 19.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/widgets/newsletter-pro',
                'screenshot_url' => null,
                'author'         => 'DevHive',
            ],
            [
                'item_type'      => 'widget',
                'name'           => 'Advanced Login',
                'slug'           => 'advanced-login',
                'description'    => 'Customizable login/register widget with social OAuth buttons.',
                'version'        => '1.0.0',
                'price'          => 14.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/widgets/advanced-login',
                'screenshot_url' => null,
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'widget',
                'name'           => 'Gallery',
                'slug'           => 'gallery',
                'description'    => 'Responsive masonry photo gallery with lightbox support.',
                'version'        => '1.1.0',
                'price'          => 12.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/widgets/gallery',
                'screenshot_url' => null,
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'widget',
                'name'           => 'Google Calendar & Maps',
                'slug'           => 'google-calendar-maps',
                'description'    => 'Embed Google Calendar events and Maps on your sidebar.',
                'version'        => '1.0.0',
                'price'          => 18.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/widgets/google-calendar-maps',
                'screenshot_url' => null,
                'author'         => 'Pubvana Team',
            ],
            [
                'item_type'      => 'widget',
                'name'           => 'YouTube Channel Feed',
                'slug'           => 'youtube-channel-feed',
                'description'    => 'Display your latest YouTube videos in a grid or list.',
                'version'        => '1.2.0',
                'price'          => 16.00,
                'is_free'        => false,
                'download_url'   => null,
                'store_url'      => 'https://pubvana.net/store/widgets/youtube-channel-feed',
                'screenshot_url' => null,
                'author'         => 'Pubvana Team',
            ],
        ];
    }

    public function fetchThemes(): array
    {
        $items = $this->fetchFromApi('theme');
        return array_map(fn($item) => (object) array_merge((array) $item, ['installed_version' => null]), $items);
    }

    public function fetchWidgets(): array
    {
        $items = $this->fetchFromApi('widget');
        return array_map(fn($item) => (object) array_merge((array) $item, ['installed_version' => null]), $items);
    }

    public function fetchAll(): array
    {
        $items = $this->fetchFromApi();
        return array_map(fn($item) => (object) array_merge((array) $item, ['installed_version' => null]), $items);
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

    public function checkUpdates(): array
    {
        $db = db_connect();
        return $db->table('marketplace_items')
            ->where('installed_version IS NOT NULL')
            ->where('installed_version != version')
            ->get()->getResultArray();
    }
}
