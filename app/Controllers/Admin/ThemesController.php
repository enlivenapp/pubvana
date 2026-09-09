<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Pubvana\Models\TrustCache;
use Pubvana\Services\RegionManager;
use Pubvana\Services\ThemeService;
use Pubvana\Models\Theme;
use Pubvana\Models\BlockPlacement;

/**
 * ThemesController - Admin UI for theme management.
 *
 * Handles theme listing, activation, options editing, and
 * block/region management within themes.
 *
 * Routes (registered via adext in core-admin.php):
 *   GET  /admin/themes                      - Theme listing
 *   POST /admin/themes/@id/activate         - Activate a theme
 *   GET  /admin/themes/@id/options          - Theme options form
 *   POST /admin/themes/@id/options          - Save theme options
 *   GET  /admin/themes/regions              - Region manager
 *   POST /admin/themes/regions/place        - Place a block in a region
 *   POST /admin/themes/regions/remove       - Remove a placement
 *   POST /admin/themes/regions/reorder      - Reorder placements
 *   POST /admin/themes/regions/move         - Move an orphaned placement
 *   POST /admin/themes/regions/values       - Save block values
 *
 * @package Pubvana\Controllers\Admin
 */
class ThemesController extends AdminController
{
    protected function service(): ThemeService
    {
        return $this->app->themes();
    }

    protected function regionManager(): RegionManager
    {
        return $this->app->regions();
    }

    /**
     * Theme listing — syncs filesystem, shows all themes with activate buttons.
     *
     * Trust statuses come from the shared trust cache; any theme with no
     * cache row is live-checked first (one batch) so fresh installs show real
     * answers. A trust-layer failure degrades to 'no data' badges.
     */
    public function index(): void
    {
        $service = $this->service();
        $service->sync();

        $themes = (new Theme($this->app->db()))->getAll();

        $trust = [];
        $maliciousActive = [];
        try {
            $this->app->trustClient()->ensureCacheForAll();
            $statuses = $this->app->trustClient()->statusesForAll();
            foreach ($themes as $theme) {
                $folder = (string) $theme->folder;
                $trust[$folder] = ['status' => 'none', 'warning' => null];

                $item = $this->app->trustClient()->themeItem($folder);
                if ($item !== null) {
                    $cached = $statuses[TrustCache::compositeKey(
                        $item['type'],
                        $item['slug'],
                        $item['version'],
                        $item['author']
                    )] ?? null;
                    if ($cached !== null) {
                        $trust[$folder] = ['status' => $cached['status'], 'warning' => $cached['warning']];
                    }
                }

                if (!empty($theme->is_active) && $trust[$folder]['status'] === TrustCache::STATUS_MALICIOUS) {
                    $maliciousActive[] = [
                        'name'    => (string) $theme->name,
                        'warning' => $trust[$folder]['warning'],
                    ];
                }
            }
        } catch (\Throwable) {
            foreach ($themes as $theme) {
                $trust[(string) $theme->folder] = ['status' => 'none', 'warning' => null];
            }
        }

        $theme_info = [];
        foreach ($themes as $theme) {
            $theme_info[$theme->folder] = $this->readThemeInfo($theme->folder);
        }

        $this->render('admin/themes/index', [
            'pageTitle'       => 'Themes',
            'themes'          => $themes,
            'validation'      => $service->getValidationResults(),
            'theme_info'      => $theme_info,
            'trust'           => $trust,
            'maliciousActive' => $maliciousActive,
        ]);
    }

    /**
     * Activate a theme.
     *
     * The trust gate runs before activation: a malicious theme is refused, an
     * unknown one needs the admin's confirmation in the modal (force=1 on the
     * confirmed resubmit), a known or trusted one proceeds untouched.
     */
    public function activate(string $id): void
    {
        $service = $this->service();
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $data = $this->app->request()->data->getData();
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $force = !empty($data['force']);

        $refusal = $this->themeTrustVerdict($theme, $force, $isAjax);
        if ($refusal !== null) {
            if (method_exists($this->app, 'session')) {
                $this->app->session()->flash('error', 'Theme ' . $refusal);
            }
            $this->app->redirect('/admin/themes');
            return;
        }

        $status = $service->activate((int) $id);

        if ($isAjax) {
            if ($status === 'activated') {
                $this->app->jsonHalt(['ok' => true]);
            }
            $errorText = match ($status) {
                'not_found' => 'Theme not found.',
                'disabled'  => 'Theme is disabled and cannot be activated.',
                'invalid'   => 'Theme failed validation (PHP detected in template files).',
                default     => 'Could not activate theme.',
            };
            // Flash also: the JS reloads after the JSON answer and the flash
            // is how the error reaches the fresh page.
            if (method_exists($this->app, 'session')) {
                $this->app->session()->flash('error', $errorText);
            }
            $this->app->jsonHalt(['ok' => false, 'error' => $errorText]);
        }

        $flash = match ($status) {
            'activated' => ['success', 'Theme activated.'],
            'not_found' => ['error', 'Theme not found.'],
            'disabled'  => ['error', 'Theme is disabled and cannot be activated.'],
            'invalid'   => ['error', 'Theme failed validation (PHP detected in template files).'],
            default     => ['error', 'Could not activate theme.'],
        };

        if (method_exists($this->app, 'session')) {
            $this->app->session()->flash($flash[0], $flash[1]);
        }

        $this->app->redirect('/admin/themes');
    }

    /**
     * Manual recheck of one theme by folder (AJAX), for screens that list
     * themes by folder rather than by DB id (the Updates screen inventory).
     */
    public function recheckByFolder(): void
    {
        $folder = (string) ($this->app->request()->data->getData()['folder'] ?? '');
        if ($folder === '') {
            $this->app->jsonHalt(['ok' => false, 'error' => 'No theme given.'], 422);
        }

        try {
            $item = $this->app->trustClient()->themeItem($folder);
        } catch (\Throwable) {
            $item = null;
        }

        if ($item === null) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'This theme has no version to check.'], 422);
        }

        $result = $this->trustLiveStatus($item);

        if (!$result['answered']) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'The trust service could not be reached.'], 503);
        }

        $this->app->jsonHalt([
            'ok'      => true,
            'status'  => $result['status'],
            'warning' => $result['warning'],
        ]);
    }

    /**
     * Manual recheck of one theme (AJAX). Re-derives the theme's identity
     * from its manifest, asks the trust service live, updates the cache, and
     * answers with the fresh status so the page can update the badge.
     */
    public function recheck(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'Unknown theme.'], 404);
        }

        try {
            $item = $this->app->trustClient()->themeItem((string) $theme->folder);
        } catch (\Throwable) {
            $item = null;
        }

        if ($item === null) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'This theme has no version to check.'], 422);
        }

        $result = $this->trustLiveStatus($item);

        if (!$result['answered']) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'The trust service could not be reached.'], 503);
        }

        $this->app->jsonHalt([
            'ok'      => true,
            'status'  => $result['status'],
            'warning' => $result['warning'],
        ]);
    }

    /**
     * Theme options form.
     */
    public function options(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $info = $this->readThemeInfo($theme->folder);
        $optionDefs = $info['provides']['options'] ?? [];

        $saved = [];
        $service = $this->service();
        foreach ($optionDefs as $key => $def) {
            if (($def['type'] ?? '') === 'group') {
                foreach (($def['fields'] ?? []) as $fKey => $fDef) {
                    $dbKey = $key . '.' . $fKey;
                    $saved[$dbKey] = $service->getThemeOption((int) $id, $dbKey, $fDef['default'] ?? '');
                }
            } else {
                $saved[$key] = $service->getThemeOption((int) $id, $key, $def['default'] ?? '');
            }
        }

        $mediaPickers = [];
        try {
            $media = $this->app->media();
            foreach ($optionDefs as $key => $def) {
                if (($def['type'] ?? '') === 'group') {
                    foreach (($def['fields'] ?? []) as $fKey => $fDef) {
                        if (($fDef['type'] ?? '') === 'media') {
                            $dbKey = $key . '.' . $fKey;
                            $mediaPickers[$dbKey] = $media->picker("options[{$key}][{$fKey}]", $saved[$dbKey] ?? '');
                        }
                    }
                } elseif (($def['type'] ?? '') === 'media') {
                    $mediaPickers[$key] = $media->picker("options[{$key}]", $saved[$key] ?? '');
                }
            }
        } catch (\Throwable) {
            $mediaPickers = [];
        }

        $this->render('admin/themes/options', [
            'pageTitle' => 'Theme Options — ' . $theme->name,
            'theme'     => $theme,
            'options'   => $optionDefs,
            'saved'     => $saved,
            'mediaPickers' => $mediaPickers,
        ]);
    }

    /**
     * Save theme options.
     */
    public function saveOptions(string $id): void
    {
        $theme = new Theme($this->app->db());
        $theme->eq('id', (int) $id)->find();

        if (!$theme->isHydrated()) {
            $this->app->redirect('/admin/themes');
            return;
        }

        $info = $this->readThemeInfo($theme->folder);
        $optionDefs = $info['provides']['options'] ?? [];

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $service = $this->service();
        $posted = $post['options'] ?? [];
        foreach ($optionDefs as $key => $def) {
            if (($def['type'] ?? '') === 'group') {
                foreach (array_keys($def['fields'] ?? []) as $fKey) {
                    $dbKey = $key . '.' . $fKey;
                    $value = $posted[$key][$fKey] ?? '';
                    $service->saveThemeOption((int) $id, $dbKey, (string) $value);
                }
            } else {
                $value = $posted[$key] ?? '';
                $service->saveThemeOption((int) $id, $key, (string) $value);
            }
        }

        if (method_exists($this->app, 'session')) {
            $this->app->session()->flash('success', 'Theme options saved.');
        }

        $this->app->redirect("/admin/themes/{$id}/options");
    }

    /**
     * Region manager — show all regions, placements, available blocks, orphans.
     */
    public function regions(): void
    {
        $rm = $this->regionManager();
        $allPlacements = $rm->getAllPlacements();

        // Load saved values for each placement
        $savedValues = [];
        foreach ($allPlacements as $regionPlacements) {
            foreach ($regionPlacements as $placement) {
                $savedValues[(int) $placement->id] = $rm->getPlacementValues((int) $placement->id);
            }
        }

        $this->render('admin/themes/regions', [
            'pageTitle'   => 'Regions',
            'regions'     => $rm->getRegions(),
            'placements'  => $allPlacements,
            'blocks'      => $rm->getAvailableBlocks(),
            'orphaned'    => $rm->getOrphanedPlacements(),
            'savedValues' => $savedValues,
        ]);
    }

    /**
     * Place a block into a region.
     */
    public function placeBlock(): void
    {
        $post = $this->app->request()->data->getData();
        $regionId = $post['region_id'] ?? '';
        $blockKey = $post['block_key'] ?? '';

        if ($regionId !== '' && $blockKey !== '') {
            $this->regionManager()->savePlacement($regionId, $blockKey);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Remove a block placement.
     */
    public function removePlacement(): void
    {
        $post = $this->app->request()->data->getData();
        $id = (int) ($post['placement_id'] ?? 0);

        if ($id > 0) {
            $this->regionManager()->removePlacement($id);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Reorder placements within a region.
     */
    public function reorderPlacements(): void
    {
        $post = $this->app->request()->data->getData();
        $regionId = $post['region_id'] ?? '';
        $ids = $post['placement_ids'] ?? [];

        if ($regionId !== '' && is_array($ids)) {
            $this->regionManager()->reorderPlacements($regionId, $ids);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Move an orphaned placement to a new region.
     */
    public function movePlacement(): void
    {
        $post = $this->app->request()->data->getData();
        $id = (int) ($post['placement_id'] ?? 0);
        $newRegionId = $post['region_id'] ?? '';

        if ($id > 0 && $newRegionId !== '') {
            $this->regionManager()->movePlacement($id, $newRegionId);
        }

        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Save block placement values (from modal edit form).
     */
    public function saveBlockValues(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $placementId = (int) ($post['placement_id'] ?? 0);
        if ($placementId <= 0) {
            $this->app->redirect('/admin/themes/regions');
            return;
        }

        // Look up the block's option definitions to identify textarea fields
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();
        $blockDef = $this->regionManager()->getAvailableBlocks()[$placement->block_key ?? ''] ?? [];
        $optionDefs = $blockDef['options'] ?? [];

        // Collect textarea field keys for sanitization
        $textareaKeys = [];
        foreach ($optionDefs as $fieldKey => $fieldDef) {
            if (($fieldDef['type'] ?? '') === 'textarea') {
                $textareaKeys[] = $fieldKey;
            }
        }

        $values = $post['values'] ?? [];
        $flat = [];

        foreach ($values as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $index => $row) {
                    if (is_array($row)) {
                        foreach ($row as $subKey => $subVal) {
                            $flat[$key . '.' . $index . '.' . $subKey] = (string) $subVal;
                        }
                    } else {
                        $flat[$key . '.' . $index] = (string) $row;
                    }
                }
            } else {
                $value = (string) $val;
                if (in_array($key, $textareaKeys, true)) {
                    $value = $this->purifyHtml($value);
                }
                $flat[$key] = $value;
            }
        }

        $this->regionManager()->savePlacementValues($placementId, $flat);
        $this->app->redirect('/admin/themes/regions');
    }

    /**
     * Sanitize HTML via HTMLPurifier.
     */
    private function purifyHtml(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        return (new \HTMLPurifier($config))->purify($html);
    }

    /**
     * The trust verdict for a theme activation, via the shared admin gate.
     */
    private function themeTrustVerdict(Theme $theme, bool $force, bool $isAjax): ?string
    {
        try {
            $item = $this->app->trustClient()->themeItem((string) $theme->folder);
        } catch (\Throwable) {
            $item = null;
        }
        if ($item === null) {
            return null;
        }

        $payload = [
            'id'      => (int) $theme->id,
            'name'    => (string) $theme->name,
            'version' => $item['version'],
        ];

        return $this->trustGate($item, 'theme', $payload, $force, $isAjax);
    }

    /**
     * Read pubvana.json for a theme folder.
     *
     * @return array<string, mixed>
     */
    private function readThemeInfo(string $folder): array
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
        $path = rtrim($root, '/') . '/themes/' . $folder . '/pubvana.json';

        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }
}
