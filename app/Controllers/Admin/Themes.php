<?php

namespace App\Controllers\Admin;

use App\Models\ThemeModel;
use App\Services\ThemeService;

class Themes extends BaseAdminController
{
    public function index(): string
    {
        $themeService = new ThemeService();
        $discovered   = $themeService->discover();
        $themes       = (new ThemeModel())->findAll();

        return $this->adminView('themes/index', array_merge($this->baseData('Themes', 'themes'), [
            'themes'     => $themes,
            'discovered' => $discovered,
        ]));
    }

    public function activate(int $id)
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', 'Permission denied.');
        }
        (new ThemeService())->activate($id);
        return redirect()->to('/admin/themes')->with('success', 'Theme activated.');
    }

    public function options(int $id): string
    {
        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
        $info     = is_file($infoFile) ? require $infoFile : [];

        $service = new ThemeService();
        $saved   = [];
        foreach (array_keys($info['options'] ?? []) as $key) {
            $saved[$key] = $service->getThemeOption($id, $key, $info['options'][$key]['default'] ?? '');
        }

        return $this->adminView('themes/options', array_merge($this->baseData('Theme Options', 'themes'), [
            'theme'   => $theme,
            'info'    => $info,
            'options' => $saved,
        ]));
    }

    public function saveOptions(int $id)
    {
        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
        $info     = is_file($infoFile) ? require $infoFile : [];
        $service  = new ThemeService();

        foreach (array_keys($info['options'] ?? []) as $key) {
            $value = $this->request->getPost($key);
            $service->saveThemeOption($id, $key, $value);
        }

        return redirect()->to("/admin/themes/{$id}/options")->with('success', 'Options saved.');
    }
}
