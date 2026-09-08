<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Pubvana\Models\BlockPlacement;
use flight\Engine;

/**
 * RegionManager - Manages content regions and block placements.
 *
 * Regions are named areas in a page layout (header, sidebar, footer, etc.).
 * Blocks are content blocks (recent posts, tag cloud, etc.) that get
 * placed into regions. This service handles:
 *
 *   - Defining available regions (platform + theme)
 *   - Managing block placements (add, remove, reorder, move)
 *   - Rendering regions by executing all placed blocks in order via Vision
 *
 * Regions come from two sources:
 *   1. Platform regions (always available): header, footer, navbar,
 *      before-content, after-content
 *   2. Theme regions: declared in themes/{name}/pubvana.json
 *
 * Blocks are registered via adext with type 'block' and provide:
 *   - label: display name
 *   - provider: callable that returns template data
 *   - template: Vision .tpl template file
 *   - options: configurable settings with defaults
 *
 * @package Pubvana\Services
 */
class RegionManager
{
    /** @var array<string, array{id: string, label: string, description: string}> Platform regions always available regardless of theme */
    protected const PLATFORM_REGIONS = [
        'footer'         => ['id' => 'footer',         'label' => 'Footer',         'description' => 'Site footer area'],
        'before-content' => ['id' => 'before-content', 'label' => 'Before Content', 'description' => 'Above main content'],
        'after-content'  => ['id' => 'after-content',  'label' => 'After Content',  'description' => 'Below main content'],
    ];

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /** @var BlockPlacement|null Cached placement model (lazy-loaded) */
    private ?BlockPlacement $placementModel = null;

    /** @var bool Whether the per-request placements map has been filled */
    private bool $placementsCacheLoaded = false;

    /** @var array<string, BlockPlacement[]> Placements keyed by region_id, lazy-loaded once per request */
    private array $placementsByRegion = [];

    /**
     * @param Engine<object> $app Flight application for accessing db(), adext(), view()
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    // -----------------------------------------------------------------
    // Region Discovery
    // -----------------------------------------------------------------

    /**
     * Get all available regions: platform + active theme's declared regions.
     *
     * Theme regions are loaded from the active theme's pubvana.json.
     * Platform regions are always included.
     *
     * @return array<string, array{id: string, label: string, description: string, source: string}> Region definitions keyed by ID
     */
    public function getRegions(): array
    {
        $regions = [];

        // Platform regions
        foreach (self::PLATFORM_REGIONS as $id => $info) {
            $regions[$id] = [
                'id'          => $id,
                'label'       => $info['label'],
                'description' => $info['description'],
                'source'      => 'platform',
            ];
        }

        // Theme-declared regions from active theme's pubvana.json
        $themeRegions = $this->getThemeDeclaredRegions();
        foreach ($themeRegions as $region) {
            $id = $region['id'];
            if (isset($regions[$id])) {
                continue;
            }
            $regions[$id] = [
                'id'          => $id,
                'label'       => $region['label'],
                'description' => $region['description'],
                'source'      => 'theme',
            ];
        }

        return $regions;
    }

    /**
     * Read theme-declared regions from the active theme's pubvana.json.
     *
     * Malformed region entries are skipped so callers get the declared
     * shape unconditionally.
     *
     * @return array<array{id: string, label: string, description: string}> Theme regions
     */
    protected function getThemeDeclaredRegions(): array
    {
        $active = $this->app->themes()->getActive();
        if (!$active) {
            return [];
        }

        $manifestPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $active->folder . DIRECTORY_SEPARATOR . 'pubvana.json';

        if (!file_exists($manifestPath)) {
            return [];
        }

        $raw = file_get_contents($manifestPath);
        if ($raw === false) {
            return [];
        }

        $manifest = json_decode($raw, true);
        $regions = $manifest['provides']['regions'] ?? [];
        if (!is_array($regions)) {
            return [];
        }

        $declared = [];
        foreach ($regions as $region) {
            if (!is_array($region)
                || !isset($region['id'])
                || !is_string($region['id'])
                || $region['id'] === '') {
                continue;
            }
            $declared[] = [
                'id'          => $region['id'],
                'label'       => is_string($region['label'] ?? null) ? $region['label'] : $region['id'],
                'description' => is_string($region['description'] ?? null) ? $region['description'] : '',
            ];
        }

        return $declared;
    }

    // -----------------------------------------------------------------
    // Block Discovery
    // -----------------------------------------------------------------

    /**
     * Get all blocks registered via adext.
     *
     * @return array<string, array<string, mixed>> Block definitions keyed by block_key
     */
    public function getAvailableBlocks(): array
    {
        $blocks = $this->app->adext()->get('block', 'available');
        uasort($blocks, fn($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));
        return $blocks;
    }

    // -----------------------------------------------------------------
    // Placement CRUD
    // -----------------------------------------------------------------

    /**
     * Get all placements for a specific region, ordered by sort_order.
     *
     * Lazy-memoizes the per-region map on first call so a page that renders
     * five regions triggers one getAll() instead of five getForRegion() calls.
     *
     * @param string $regionId Region identifier
     * @return BlockPlacement[] Placements ordered by sort_order
     */
    public function getPlacements(string $regionId): array
    {
        $this->loadPlacementsCache();
        return $this->placementsByRegion[$regionId] ?? [];
    }

    /**
     * Fill the placements cache once per request (one query for all regions).
     */
    protected function loadPlacementsCache(): void
    {
        if ($this->placementsCacheLoaded) {
            return;
        }
        $this->placementsCacheLoaded = true;
        $this->placementsByRegion = $this->getAllPlacements();
    }

    /**
     * Get all placements grouped by region.
     *
     * @return array<string, BlockPlacement[]> Placements keyed by region_id
     */
    public function getAllPlacements(): array
    {
        $all = $this->placements()->getAll();
        $grouped = [];

        foreach ($all as $placement) {
            $grouped[$placement->region_id][] = $placement;
        }

        return $grouped;
    }

    /**
     * Place a block in a region.
     *
     * Creates a new placement at the next available sort_order.
     * If the block is already in the region, does nothing.
     *
     * @param string $regionId Region identifier
     * @param string $blockKey Block key from adext registration
     * @return BlockPlacement|null The created placement, or null if block already placed
     */
    public function savePlacement(string $regionId, string $blockKey): ?BlockPlacement
    {
        $existing = $this->placements()->findPlacement($regionId, $blockKey);
        if ($existing !== null) {
            return null;
        }

        $placement = new BlockPlacement($this->app->db());
        $placement->region_id = $regionId;
        $placement->block_key = $blockKey;
        $placement->sort_order = $this->placements()->nextSortOrder($regionId);
        $placement->created_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $placement->insert();

        return $placement;
    }

    /**
     * Remove a placement by ID.
     *
     * @param int $placementId Placement primary key
     */
    public function removePlacement(int $placementId): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if ($placement->isHydrated()) {
            $placement->delete();
        }
    }

    /**
     * Reorder placements within a region.
     *
     * @param string $regionId Region identifier
     * @param int[]  $placementIds Placement IDs in desired order
     */
    public function reorderPlacements(string $regionId, array $placementIds): void
    {
        foreach ($placementIds as $index => $placementId) {
            $placement = new BlockPlacement($this->app->db());
            $placement->eq('id', (int) $placementId)
                      ->eq('region_id', $regionId)
                      ->find();

            if ($placement->isHydrated()) {
                $placement->sort_order = $index;
                $placement->save();
            }
        }
    }

    /**
     * Move a placement from one region to another.
     *
     * If the block already exists in the target region, removes the old one.
     *
     * @param int    $placementId Placement to move
     * @param string $newRegionId Destination region
     */
    public function movePlacement(int $placementId, string $newRegionId): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return;
        }

        // Check for duplicate in target region
        $existing = $this->placements()->findPlacement($newRegionId, $placement->block_key);
        if ($existing) {
            $placement->delete();
            return;
        }

        $placement->region_id = $newRegionId;
        $placement->sort_order = $this->placements()->nextSortOrder($newRegionId);
        $placement->save();
    }

    /**
     * Get placements whose region_id doesn't match any current region.
     *
     * These are orphaned when a theme switch removes theme-declared regions.
     *
     * @return BlockPlacement[]
     */
    public function getOrphanedPlacements(): array
    {
        $regions = $this->getRegions();
        $all = $this->placements()->getAll();
        $orphaned = [];

        foreach ($all as $placement) {
            if (!isset($regions[$placement->region_id])) {
                $orphaned[] = $placement;
            }
        }

        return $orphaned;
    }

    // -----------------------------------------------------------------
    // Block Option Values (JSON column)
    // -----------------------------------------------------------------

    /**
     * Get saved values for a placement as a nested array.
     *
     * @param int $placementId Placement ID
     * @return array<string, mixed> Nested values
     */
    public function getPlacementValues(int $placementId): array
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return [];
        }

        return $placement->getOptions();
    }

    /**
     * Save values for a placement.
     *
     * @param int                  $placementId Placement ID
     * @param array<string, mixed> $values      Key => value pairs (nested or flat)
     */
    public function savePlacementValues(int $placementId, array $values): void
    {
        $placement = new BlockPlacement($this->app->db());
        $placement->eq('id', $placementId)->find();

        if (!$placement->isHydrated()) {
            return;
        }

        $placement->setOptions($values);
        $placement->save();
    }

    // -----------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------

    /**
     * Render all placed blocks for a single region.
     *
     * @param string $regionId Region to render
     * @return string Concatenated HTML output from all blocks in the region
     */
    public function buildRegion(string $regionId): string
    {
        $blocks = $this->getAvailableBlocks();
        return $this->renderRegion($regionId, $blocks);
    }

    /**
     * Render all blocks in a region via Vision.
     *
     * @param string                              $regionId Region identifier
     * @param array<string, array<string, mixed>> $blocks   Available block definitions
     * @return string Rendered HTML
     */
    protected function renderRegion(string $regionId, array $blocks): string
    {
        $placements = $this->getPlacements($regionId);

        if (empty($placements)) {
            return '';
        }

        $view = $this->app->view();
        $vision = ($view instanceof PluginView) ? $view->vision() : null;

        if ($vision === null) {
            return '';
        }

        $html = '';

        foreach ($placements as $placement) {
            $block = $blocks[$placement->block_key] ?? null;

            if ($block === null) {
                continue;
            }

            $options = $placement->getOptions();
            $rendered = $this->renderBlock($block, $options, $vision, $view);
            if ($rendered !== '') {
                $html .= $rendered;
            }
        }

        return $html;
    }

    /**
     * Render a single block: call provider with saved values, resolve template, render with Vision.
     *
     * @param array<string, mixed> $block   Block definition from adext
     * @param array<string, mixed> $options Saved placement options (JSON-decoded)
     * @param \Enlivenapp\Vision\Engine $vision Vision template engine instance
     * @param PluginView                $view   PluginView instance for template resolution
     * @return string Rendered HTML, or empty string on failure
     */
    protected function renderBlock(array $block, array $options, object $vision, PluginView $view): string
    {
        // Call the data provider with saved placement values
        $data = [];
        if (isset($block['provider']) && is_callable($block['provider'])) {
            try {
                $data = $block['provider']($options);
                if (!is_array($data)) {
                    $data = [];
                }
            } catch (\Throwable $e) {
                return '';
            }
        } else {
            // No provider — pass saved options directly as template data
            $data = $options;
        }

        // Resolve template path through the override chain
        $templatePath = $this->resolveBlockTemplate($block['template'] ?? '', $view);

        if ($templatePath === '' || !is_file($templatePath)) {
            return '';
        }

        try {
            return $vision->render($templatePath, $data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Resolve a block template path through the three-tier override chain.
     *
     * Template format: 'pubvana/blog/public/blocks/recent-posts'
     * Resolution:
     *   1. app/Views/pubvana/blog/public/blocks/recent-posts.tpl  (owner override)
     *   2. themes/{active}/Views/pubvana/blog/public/blocks/recent-posts.tpl  (theme override)
     *   3. vendor/pubvana/blog/src/Views/public/blocks/recent-posts.tpl  (plugin default)
     *
     * @param string     $template Template path (without .tpl extension)
     * @param PluginView $view     PluginView for theme/plugin path lookups
     * @return string Resolved absolute path, or empty string if not found
     */
    protected function resolveBlockTemplate(string $template, PluginView $view): string
    {
        if ($template === '') {
            return '';
        }

        // Parse package name from template path (first two segments)
        $parts = explode('/', $template);
        if (count($parts) < 3) {
            return '';
        }

        $packageName = $parts[0] . '/' . $parts[1];
        $relativePath = implode('/', array_slice($parts, 2)) . '.tpl';
        $prefixedPath = $template . '.tpl';

        // 1. App-level override
        $appViewsPath = $this->app->get('flight.views.path') ?? PROJECT_ROOT . '/app/Views';
        $appOverride = $appViewsPath . DIRECTORY_SEPARATOR . $prefixedPath;
        if (is_file($appOverride)) {
            return $appOverride;
        }

        // 2. Theme override
        $themePath = $view->getThemePath();
        if ($themePath !== null) {
            $themeOverride = $themePath . DIRECTORY_SEPARATOR . $prefixedPath;
            if (is_file($themeOverride)) {
                return $themeOverride;
            }
        }

        // 3. Plugin default
        $pluginViewPath = $view->getPluginPath($packageName);
        if ($pluginViewPath !== null) {
            $pluginFile = $pluginViewPath . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($pluginFile)) {
                return $pluginFile;
            }
        }

        return '';
    }

    // -----------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------

    /**
     * Get the placement model instance (lazy-loaded).
     *
     * @return BlockPlacement
     */
    protected function placements(): BlockPlacement
    {
        if ($this->placementModel === null) {
            $this->placementModel = new BlockPlacement($this->app->db());
        }
        return $this->placementModel;
    }
}
