<?php

namespace App\Services;

use App\Models\ThemeModel;
use App\Models\WidgetAreaModel;

class ThemeService
{
    protected ?object $activeTheme = null;

    public function discover(): array
    {
        $themes = [];
        foreach (glob(THEMES_PATH . '*', GLOB_ONLYDIR) as $dir) {
            $infoFile = $dir . '/theme_info.php';
            if (is_file($infoFile)) {
                $info = require $infoFile;
                $info['folder'] = basename($dir);
                $themes[]       = $info;
            }
        }
        return $themes;
    }

    public function sync(): void
    {
        $model = new ThemeModel();
        $now   = date('Y-m-d H:i:s');

        foreach ($this->discover() as $info) {
            $folder = $info['folder'];
            if (! $model->where('folder', $folder)->first()) {
                $model->insert([
                    'name'         => $info['name']    ?? $folder,
                    'folder'       => $folder,
                    'version'      => $info['version'] ?? '1.0.0',
                    'is_active'    => 0,
                    'installed_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
            // Ensure asset symlink exists even if theme has never been activated
            $this->symlinkAssets($folder);
        }
    }

    public function getActive(): ?object
    {
        if ($this->activeTheme !== null) {
            return $this->activeTheme;
        }
        $model = new ThemeModel();
        $this->activeTheme = $model->where('is_active', 1)->first();
        return $this->activeTheme;
    }

    public function view(string $name, array $data = []): string
    {
        $theme = $this->getActive();
        if (! $theme) {
            return '<p>No active theme.</p>';
        }

        $path = THEMES_PATH . $theme->folder . '/views/' . $name . '.php';

        // Fall back to parent theme if the view isn't in the active theme
        if (! is_file($path)) {
            $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
            $info     = is_file($infoFile) ? (require $infoFile) : [];
            $parent   = $info['parent'] ?? null;
            if ($parent) {
                $path = THEMES_PATH . $parent . '/views/' . $name . '.php';
            }
        }

        if (! is_file($path)) {
            return '<p>Theme view not found: ' . esc($name) . '</p>';
        }

        // CI4's view() doesn't support absolute paths; render via extract+include
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function activate(int $id): bool
    {
        $model = new ThemeModel();
        $theme = $model->find($id);
        if (! $theme) {
            return false;
        }

        $model->where('id !=', $id)->set('is_active', 0)->update();
        $model->update($id, ['is_active' => 1]);

        $this->activeTheme = null;
        $this->syncWidgetAreas($theme);
        $this->symlinkAssets($theme->folder);

        return true;
    }

    protected function syncWidgetAreas(object $theme): void
    {
        $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
        if (! is_file($infoFile)) {
            return;
        }
        $info  = require $infoFile;
        $areas = $info['widget_areas'] ?? [];

        $areaModel = new WidgetAreaModel();

        // Build a map of existing areas by slug so we don't delete instances
        $existing = [];
        foreach ($areaModel->where('theme_id', $theme->id)->findAll() as $row) {
            $existing[$row->slug] = $row;
        }

        // Insert only areas that don't already exist; update name if it changed
        foreach ($areas as $slug => $name) {
            if (isset($existing[$slug])) {
                if ($existing[$slug]->name !== $name) {
                    $areaModel->update($existing[$slug]->id, ['name' => $name]);
                }
                unset($existing[$slug]);
            } else {
                $areaModel->insert([
                    'name'     => $name,
                    'slug'     => $slug,
                    'theme_id' => $theme->id,
                ]);
            }
        }

        // Remove areas no longer in theme_info (slug removed from theme)
        foreach ($existing as $obsolete) {
            $areaModel->delete($obsolete->id);
        }
    }

    public function symlinkAssets(string $folder): void
    {
        // Reject any folder name containing path separators or non-safe characters
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) {
            throw new \RuntimeException('Invalid theme folder name: ' . $folder);
        }

        $target     = THEMES_PATH . $folder . '/assets';
        $link       = FCPATH . 'themes/' . $folder;
        $themesReal = realpath(THEMES_PATH);

        // Verify resolved target stays within THEMES_PATH
        if ($themesReal && is_dir($target)) {
            $targetReal = realpath($target);
            if ($targetReal === false || strpos($targetReal, $themesReal) !== 0) {
                throw new \RuntimeException('Theme assets path resolves outside THEMES_PATH.');
            }
        }

        if (is_link($link)) {
            unlink($link);
        }
        if (is_dir($target) && ! is_link($link)) {
            symlink($target, $link);
        }
    }

    public function getThemeOption(int $themeId, string $key, mixed $default = null): mixed
    {
        $db  = db_connect();
        $row = $db->table('theme_options')
            ->where('theme_id', $themeId)
            ->where('option_key', $key)
            ->get()->getRowObject();

        return $row ? $row->option_value : $default;
    }

    public function saveThemeOption(int $themeId, string $key, mixed $value): void
    {
        $db  = db_connect();
        $row = $db->table('theme_options')
            ->where('theme_id', $themeId)
            ->where('option_key', $key)
            ->get()->getRowObject();

        if ($row) {
            $db->table('theme_options')
                ->where('theme_id', $themeId)
                ->where('option_key', $key)
                ->update(['option_value' => $value]);
        } else {
            $db->table('theme_options')->insert([
                'theme_id'     => $themeId,
                'option_key'   => $key,
                'option_value' => $value,
            ]);
        }
    }
}
