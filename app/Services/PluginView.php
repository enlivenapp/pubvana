<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\template\View;

/**
 * PluginView - Extends Flight's View with 3-tier template resolution.
 *
 * Handles two rendering modes:
 *   Admin routes (/admin/*): Native PHP templates (.php)
 *   Public routes: Vision templates (.tpl) - no PHP execution
 *
 * Template resolution order:
 *   1. app/Views/{plugin}/{file}               - Site owner override
 *   2. themes/{active}/Views/{plugin}/{file}   - Theme override
 *   3. plugins/{plugin}/Views/{file}           - Plugin default
 *
 * Plugins register view dirs via addPluginPath().
 * PluginViewContextMiddleware sets the active plugin before each controller.
 *
 * @package Pubvana\Services
 */
class PluginView extends View
{
    /** @var array<string, string> Plugin ID => absolute path to Views/ dir */
    protected array $pluginPaths = [];

    /** @var string|null Active plugin ID, set by PluginViewContextMiddleware */
    protected ?string $currentPlugin = null;

    /** @var string[] Route prefixes using native PHP rendering (no Vision) */
    protected array $nativeRenderPrefixes = ['/admin'];

    /** @var string|null Active theme's Views/ dir for theme override tier */
    protected ?string $themePath = null;

    /** @var \Enlivenapp\Vision\Engine|null Vision template engine instance (lazy-loaded) */
    private ?\Enlivenapp\Vision\Engine $visionEngine = null;

    /**
     * Register a plugin's Views/ directory for template resolution.
     *
     * @param string $pluginId  Plugin ID (e.g. 'pubvana/blog')
     * @param string $viewPath  Absolute path to the plugin's Views/ directory
     */
    public function addPluginPath(string $pluginId, string $viewPath): void
    {
        $this->pluginPaths[$pluginId] = rtrim($viewPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Get a registered plugin's Views/ directory path.
     *
     * @param string $pluginId  Plugin ID (e.g. 'pubvana/blog')
     * @return string|null Absolute path, or null if not registered
     */
    public function getPluginPath(string $pluginId): ?string
    {
        return $this->pluginPaths[$pluginId] ?? null;
    }

    /**
     * Set the active plugin for automatic view resolution.
     *
     * When set, un-prefixed render('login') resolves to this plugin's views.
     * Set by PluginViewContextMiddleware, cleared after the controller runs.
     *
     * @param string|null $pluginId Plugin ID, or null to clear
     */
    public function setCurrentPlugin(?string $pluginId): void
    {
        $this->currentPlugin = $pluginId;
    }

    /**
     * Set the active theme's Views/ directory for the theme override tier.
     *
     * @param string|null $path Absolute path to theme's Views/ directory
     */
    public function setThemePath(?string $path): void
    {
        $this->themePath = $path !== null ? rtrim($path, DIRECTORY_SEPARATOR) : null;
    }

    /** @return string|null Active theme's Views/ directory path */
    public function getThemePath(): ?string
    {
        return $this->themePath;
    }

    /**
     * Add a route prefix that should use native PHP rendering.
     *
     * @param string $prefix URL prefix (e.g. '/api')
     */
    public function addNativeRenderPrefix(string $prefix): void
    {
        $this->nativeRenderPrefixes[] = $prefix;
    }

    /** @return bool True if enlivenapp/vision is installed */
    protected function hasVision(): bool
    {
        return class_exists(\Enlivenapp\Vision\Engine::class);
    }

    /**
     * Get the Vision engine instance (lazy-loaded).
     *
     * Vision provides: {{ var }}, {% if %}, {% for %}, {% extends %},
     * {% block %}, {% csrf_field %}, and custom tags.
     *
     * {% block %} is Vision's inheritance slot, unrelated to the region
     * system's content blocks (see {% region %} below).
     *
     * @return \Enlivenapp\Vision\Engine|null Vision engine, or null if not installed
     */
    public function vision(): ?\Enlivenapp\Vision\Engine
    {
        if ($this->visionEngine === null && $this->hasVision()) {
            $this->visionEngine = new \Enlivenapp\Vision\Engine();
            $this->registerDefaultTags();
        }
        return $this->visionEngine;
    }

    /**
     * Register built-in Vision tags available in all templates.
     *
     * Tags:
     *   {% csrf_field %}  - outputs a hidden CSRF token input
     *   {% region 'id' %} - renders a content region with all its blocks.
     *                      These blocks are the region system's content blocks,
     *                      not Vision's {% block %} tag.
     */
    protected function registerDefaultTags(): void
    {
        if ($this->visionEngine === null) {
            return;
        }

        $app = \Flight::app();

        $this->visionEngine->tags()->register('csrf_field', function () {
            return function_exists('csrf_field') ? csrf_field() : '';
        });

        $this->visionEngine->tags()->register('region', function (string $regionId) use ($app) {
            try {
                return $app->regions()->buildRegion($regionId);
            } catch (\Throwable $e) {
                return '';
            }
        });

        // Captcha widget for a protected area: prints the provider's div
        // and script tag, or nothing when that area is not protected.
        // Usage in a template: {% captcha 'comments' %}
        $this->visionEngine->tags()->register('captcha', function (string $area) use ($app) {
            try {
                return $app->captcha()->snippetFor($area);
            } catch (\Throwable $e) {
                return '';
            }
        });
    }

    /**
     * Check if the current request should use native PHP rendering.
     *
     * Admin routes always use PHP templates for security.
     */
    protected function isNativeRender(): bool
    {
        $parsedPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $path = is_string($parsedPath) ? $parsedPath : '';

        // Strip the front-controller base (e.g. /public when index.php lives
        // in public/ and the redirect() helper prepends request()->base).
        // This makes /public/admin/... resolve exactly like /admin/...
        try {
            $base = \Flight::app()->request()->base ?? '';
        } catch (\Throwable) {
            $base = '';
        }
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // Strip base URL prefix (e.g. /public) so /public/admin matches /admin
        $parsedBase = parse_url(\Flight::get('CMS.siteUrl') ?? '', PHP_URL_PATH);
        $baseUrl = rtrim(is_string($parsedBase) ? $parsedBase : '', '/');
        if ($baseUrl !== '' && str_starts_with($path, $baseUrl)) {
            $path = substr($path, strlen($baseUrl));
        }
        foreach ($this->nativeRenderPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public function fetch(string $file, ?array $data = null): string
    {
        if (!$this->hasVision() || $this->isNativeRender()) {
            $this->extension = '.php';
        } else {
            $this->extension = '.tpl';
        }
        return parent::fetch($file, $data);
    }

    /**
     * Render a template with the appropriate engine.
     *
     * Admin routes: Flight's native PHP include-based rendering.
     * Public routes: Vision .tpl rendering - no PHP execution.
     *
     * @param string     $file         Template name (e.g. 'pubvana/blog/index')
     * @param array<string, mixed>|null $templateData Variables to pass to the template
     */
    public function render(string $file, ?array $templateData = null): void
    {
        // Admin routes use native PHP templates for security
        $vision = $this->vision();
        if (!$vision || $this->isNativeRender()) {
            $this->extension = '.php';
            parent::render($file, $templateData);
            return;
        }

        // Public routes use Vision .tpl templates
        $this->extension = '.tpl';
        $template = $this->getTemplate($file);

        if (!file_exists($template)) {
            throw new \Exception("Template not found: {$template}");
        }

        $data = $this->vars;
        if (is_array($templateData)) {
            $data = array_merge($data, $templateData);
            if ($this->preserveVars) {
                $this->vars = array_merge($this->vars, $templateData);
            }
        }

        // Theme's Views/ as basePath so includes/extends resolve through the theme
        $basePath = $this->themePath
            ? ($this->themePath . DIRECTORY_SEPARATOR)
            : (dirname($template) . '/');

        echo $vision->render($template, $data, $basePath);
    }

    /**
     * Resolve a template name to its full filesystem path.
     *
     * Resolution order for 'pubvana/blog/index':
     *   1. app/Views/pubvana/blog/index.php       (owner override)
     *   2. themes/default/Views/pubvana/blog/index.tpl (theme override)
     *   3. plugins/blog/Views/index.php            (plugin default)
     *
     * For un-prefixed views during a plugin route (currentPlugin set):
     *   Same chain using the active plugin's ID as prefix.
     *
     * For non-plugin views (e.g. 'admin/layouts/admin'):
     *   Standard Flight behavior: app/Views/{file}
     *
     * @param string $file Template name
     * @return string Resolved absolute path
     */
    public function getTemplate(string $file): string
    {
        $ext = $this->extension;

        // Append extension if not already present
        if (!empty($ext) && substr($file, -strlen($ext)) !== $ext) {
            $file .= $ext;
        }

        // Absolute paths pass through unchanged
        if (substr($file, 0, 1) === '/') {
            return $file;
        }

        // Check for explicit plugin prefix (e.g. 'pubvana/blog/index')
        foreach ($this->pluginPaths as $pluginId => $pluginViewPath) {
            $prefix = $pluginId . '/';
            if (str_starts_with($file, $prefix)) {
                $relativeFile = substr($file, strlen($prefix));
                return $this->resolvePluginView($pluginId, $pluginViewPath, $relativeFile, $file);
            }
        }

        // Use active plugin context if no explicit prefix
        if ($this->currentPlugin !== null && isset($this->pluginPaths[$this->currentPlugin])) {
            $pluginViewPath = $this->pluginPaths[$this->currentPlugin];
            $prefixedFile = $this->currentPlugin . '/' . $file;
            $resolved = $this->resolvePluginView($this->currentPlugin, $pluginViewPath, $file, $prefixedFile);
            if (file_exists($resolved)) {
                return $resolved;
            }
        }

        // Default: standard Flight path resolution
        return $this->path . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Resolve a view within the 3-tier override chain.
     *
     * @param string $pluginId     Plugin ID (e.g. 'pubvana/blog')
     * @param string $pluginViewPath Absolute path to plugin's Views/ dir
     * @param string $relativeFile  Filename relative to Views/ (e.g. 'index.tpl')
     * @param string $prefixedFile  Full prefixed path for overrides (e.g. 'pubvana/blog/index.tpl')
     * @return string Resolved absolute path
     */
    protected function resolvePluginView(
        string $pluginId,
        string $pluginViewPath,
        string $relativeFile,
        string $prefixedFile
    ): string {
        // Tier 1: Site owner override in app/Views/
        $overridePath = $this->path . DIRECTORY_SEPARATOR . $prefixedFile;
        if (file_exists($overridePath)) {
            return $overridePath;
        }

        // Tier 2: Theme override in themes/{active}/Views/
        if ($this->themePath !== null) {
            $themeOverride = $this->themePath . DIRECTORY_SEPARATOR . $prefixedFile;
            if (file_exists($themeOverride)) {
                return $themeOverride;
            }
        }

        // Tier 3: Plugin default in plugins/{plugin}/Views/
        return $pluginViewPath . DIRECTORY_SEPARATOR . $relativeFile;
    }
}
