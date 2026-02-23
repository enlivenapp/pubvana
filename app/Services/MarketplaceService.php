<?php

namespace App\Services;

class MarketplaceService
{
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
                'download_url'   => 'https://pubvana.org/marketplace/download/aurora',
                'store_url'      => null,
                'screenshot_url' => 'https://pubvana.org/marketplace/screenshots/aurora.png',
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
                'store_url'      => 'https://pubvana.org/store/themes/nightfall',
                'screenshot_url' => 'https://pubvana.org/marketplace/screenshots/nightfall.png',
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
                'download_url'   => 'https://pubvana.org/marketplace/download/minimal',
                'store_url'      => null,
                'screenshot_url' => 'https://pubvana.org/marketplace/screenshots/minimal.png',
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
                'store_url'      => 'https://pubvana.org/store/themes/portfolio-pro',
                'screenshot_url' => 'https://pubvana.org/marketplace/screenshots/portfolio-pro.png',
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
                'download_url'   => 'https://pubvana.org/marketplace/download/instagram-feed',
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
                'store_url'      => 'https://pubvana.org/store/widgets/newsletter-pro',
                'screenshot_url' => null,
                'author'         => 'DevHive',
            ],
        ];
    }

    public function fetchThemes(): array
    {
        return array_map(fn($item) => (object) array_merge($item, ['installed_version' => null]), $this->mockThemes());
    }

    public function fetchWidgets(): array
    {
        return array_map(fn($item) => (object) array_merge($item, ['installed_version' => null]), $this->mockWidgets());
    }

    public function fetchAll(): array
    {
        return array_merge($this->fetchThemes(), $this->fetchWidgets());
    }

    public function installFree(string $downloadUrl, string $type, string $folder): bool
    {
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
