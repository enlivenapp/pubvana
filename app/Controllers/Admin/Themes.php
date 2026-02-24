<?php

namespace App\Controllers\Admin;

use App\Models\ThemeModel;
use App\Services\ThemeService;

class Themes extends BaseAdminController
{
    public function index(): string
    {
        $themeService = new ThemeService();
        $themeService->sync();
        $themes = (new ThemeModel())->findAll();

        return $this->adminView('themes/index', array_merge($this->baseData('Themes', 'themes'), [
            'themes' => $themes,
        ]));
    }

    public function activate(int $id)
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', 'Permission denied.');
        }
        $ok = (new ThemeService())->activate($id);
        if (! $ok) {
            return redirect()->to('/admin/themes')->with('error', 'Cannot activate theme — license is invalid. Re-install or contact support.');
        }
        return redirect()->to('/admin/themes')->with('success', 'Theme activated.');
    }

    public function options(int $id): string
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', 'Permission denied.');
        }

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
            'options' => $info['options'] ?? [],  // definitions (type, label, default)
            'saved'   => $saved,                  // current saved values
        ]));
    }

    public function saveOptions(int $id)
    {
        if (! auth()->user()->can('admin.themes')) {
            return redirect()->to('/admin/themes')->with('error', 'Permission denied.');
        }

        $theme = (new ThemeModel())->find($id);
        if (! $theme) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $infoFile = THEMES_PATH . $theme->folder . '/theme_info.php';
        $info     = is_file($infoFile) ? require $infoFile : [];
        $service  = new ThemeService();

        $posted = $this->request->getPost('options') ?? [];
        foreach (array_keys($info['options'] ?? []) as $key) {
            $value = $posted[$key] ?? null;
            $service->saveThemeOption($id, $key, $value);
        }

        return redirect()->to("/admin/themes/{$id}/options")->with('success', 'Options saved.');
    }
}
