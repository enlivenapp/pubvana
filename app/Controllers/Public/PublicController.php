<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Public;

use flight\Engine;

/**
 * PublicController - Base controller for all public-facing routes.
 *
 * Builds the full layout data structure (site, header, nav, theme_options,
 * breadcrumbs, scripts_footer) and merges it with route-specific data before
 * rendering through the theme's Vision template system.
 *
 * Child controllers call $this->render($template, $data) which injects
 * all global layout data. The layout template and its partials receive
 * a complete, consistent data set on every request.
 *
 * @package Pubvana\Controllers\Public
 */
abstract class PublicController
{
    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;

    /** @var string Config key prefix for this plugin's settings */
    protected string $configPrepend;

    /**
     * @param Engine<object> $app            The FlightPHP app instance
     * @param string         $configPrepend  Config key prefix for plugin settings
     */
    public function __construct(Engine $app, string $configPrepend = 'pubvana')
    {
        $this->app = $app;
        $this->configPrepend = $configPrepend;
    }

    /**
     * Render a public view with full layout data.
     *
     * Builds the global data structure, merges it with route-specific data,
     * sets the page title, and renders through PluginView's template
     * resolution chain (app override → theme → plugin default).
     *
     * @param string               $template  Template name (e.g. 'page', 'pubvana/blog/post')
     * @param array<string, mixed> $data      Route-specific variables (title, content, etc.)
     */
    protected function render(string $template, array $data = []): void
    {
        // Run the content through the registered content.render transformers
        // centrally. Plugins pass raw content; PublicController is the boss
        // of how it is processed before reaching the theme template.
        if (isset($data['content']) && is_string($data['content'])) {
            $data['content'] = $this->app->content()->render($data['content']);
        }

        $global = $this->buildGlobalData($data);
        $viewData = array_merge($global, $data);

        // Page-specific title overrides the default
        $siteName = $this->getSiteName();
        $pageTitle = $data['title'] ?? $data['archive_title'] ?? 'Home';

        // SEO integration: when the SEO plugin is enabled, it owns the full
        // head output (title, meta, canonical, structured data). Guarded so a
        // disabled plugin leaves untouched the simple title fallback below.
        // A controller that already loaded the content passes its SEO context
        // via $data['seo_context']; detectContent() then never re-queries the
        // same post/page the controller already fetched.
        $seo = null;
        try {
            $seo = $this->app->seo();
        } catch (\Throwable) {
        }

        if ($seo !== null) {
            $seoContext = $data['seo_context'] ?? null;
            if (is_array($seoContext)) {
                $seo->setContext($seoContext);
            } else {
                $seo->detectContent();
            }
            $context = $seo->getContext();
            if (empty($context['title'])) {
                $context['title'] = $pageTitle;
                $seo->setContext($context);
            }
            $viewData['header']['title'] = $seo->buildTitle();
            $viewData['header']['seo'] = $seo->renderHead();
        } else {
            $viewData['header']['title'] = $pageTitle . ' - ' . $siteName;
        }

        // Build complete head HTML string from the header array
        $viewData['header'] = $this->buildHeadHtml($viewData['header']);

        // Resolve the theme-owned template (app override → theme root →
        // plugin fallback) and render. The theme's Views/ is the basePath, so
        // extends/includes/regions all resolve from the theme. The theme (and
        // PublicController) decides layout; a plugin never controls it.
        $view = $this->app->view();

        // Sync the View basePath with the active theme from the database.
        // services.php keys themePath off the 'active_theme' config key, which
        // nothing sets, so it would stay 'default'. resolveTemplate() reads
        // the DB (getActiveThemeName) and picks the right top-level file, but
        // {% extends %}, {% include %}, and region block overrides resolve
        // against themePath: without this sync, a themed child template
        // renders inside the default theme's layout.
        if ($view instanceof \Pubvana\Services\PluginView) {
            try {
                $activeTheme = $this->app->themes()->getActive();
                if ($activeTheme !== null) {
                    $activeThemePath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes'
                        . DIRECTORY_SEPARATOR . $activeTheme->folder
                        . DIRECTORY_SEPARATOR . 'Views';
                    if ($view->getThemePath() !== rtrim($activeThemePath, DIRECTORY_SEPARATOR)) {
                        $view->setThemePath($activeThemePath);
                    }
                }
            } catch (\Throwable) {
                // Themes table missing on fresh installs; boot already
                // placed a fallback theme path.
            }
        }

        $templateFile = $this->resolveTemplate($template, $view);

        if ($view instanceof \Pubvana\Services\PluginView) {
            $view->render($templateFile, $viewData);
            return;
        }

        $this->app->render($template, $viewData);
    }

    /**
     * Resolve a public template through the three-tier chain:
     *   1. app/Views/{plugin}/{template}.tpl  - site owner override
     *   2. themes/{active}/Views/{template}.tpl - theme root (the layout owner)
     *   3. plugins/{Plugin}/Views/{template}.tpl - plugin fallback
     *
     * @param string $template Template name (e.g. 'home', 'post', 'categories')
     * @param object $view     The PluginView instance for plugin path lookup
     * @return string Resolved absolute path
     */
    protected function resolveTemplate(string $template, object $view): string
    {
        $appViews = (string) ($this->app->get('flight.views.path') ?? PROJECT_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views');
        $themeName = $this->getActiveThemeName();
        $routePrepend = $this->getRoutePrepend();

        $candidates = [];

        // 1. App override: app/Views/{routePrepend}/{template}.tpl
        if ($routePrepend !== '') {
            $candidates[] = $appViews . DIRECTORY_SEPARATOR . $routePrepend
                . DIRECTORY_SEPARATOR . $template . '.tpl';
        }

        // 2. Theme root: themes/{active}/Views/{template}.tpl
        $themeCandidate = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes'
            . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'Views'
            . DIRECTORY_SEPARATOR . $template . '.tpl';
        $candidates[] = $themeCandidate;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // 3. Plugin fallback: plugin's Views/{template}.tpl
        if ($view instanceof \Pubvana\Services\PluginView) {
            $pluginPath = $view->getPluginPath($this->getPluginId());
            if ($pluginPath !== null) {
                $pluginFile = $pluginPath . DIRECTORY_SEPARATOR . $template . '.tpl';
                if (is_file($pluginFile)) {
                    return $pluginFile;
                }
            }
        }

        // Fall back to the theme root candidate so the engine reports a
        // meaningful missing-template error against the theme.
        return $themeCandidate;
    }

    /**
     * Get this controller's plugin id from the config prepend.
     *
     * @return string Plugin id (e.g. 'pubvana/blog'), or '' if not a plugin
     */
    protected function getPluginId(): string
    {
        if ($this->configPrepend === 'pubvana') {
            return '';
        }
        return str_replace('.', '/', $this->configPrepend);
    }

    /**
     * Get this controller's public route prefix (routePrepend).
     *
     * @return string URL prefix without leading slash (e.g. 'blog')
     */
    protected function getRoutePrepend(): string
    {
        $pluginId = $this->getPluginId();
        if ($pluginId === '') {
            return '';
        }

        try {
            $prefix = $this->app->pluginLoader()->routePrefix($pluginId);
        } catch (\Throwable) {
            return '';
        }

        return ltrim($prefix, '/');
    }

    // -----------------------------------------------------------------
    // Global Data
    // -----------------------------------------------------------------

    /**
     * Build the complete data structure expected by the layout template.
     *
     * Every public page receives this data. Route-specific data (title,
     * content, etc.) is merged on top by render().
     *
     * @param array<string, mixed> $routeData Variables from the child controller
     * @return array<string, mixed> Complete layout data
     */
    protected function buildGlobalData(array $routeData = []): array
    {
        $siteName = $this->getSiteName();

        return [
            'site' => [
                'name'        => $siteName,
                'url'         => $this->app->get('flight.base_url') ?? '/',
                'description' => $this->app->settings()->get('CMS.siteByline') ?? '',
                'logo'        => $this->app->settings()->get('CMS.logo') ?? '',
                'favicon'     => $this->app->settings()->get('CMS.favicon') ?? '/favicon.ico',
                'copyright'   => $this->app->settings()->get('CMS.copyright') ?? '© ' . date('Y') . ' ' . $siteName,
            ],
            'header' => [
                'title' => 'Home - ' . $siteName,
                'meta'  => [],
                'og'    => [],
                'seo'   => '',
                'head'  => $this->getPublicHead(),
                'css'   => $this->getPublicCss(),
            ],
            'nav'            => $this->getNavigation('primary'),
            'nav_footer'     => $this->getNavigation('footer'),
            'comments_html'  => $this->buildCommentsHtml($routeData),
            'theme_options'  => $this->getThemeOptions(),
            'breadcrumbs'    => $this->buildBreadcrumbs($routeData),
            'flash'          => $this->buildFlash(),
            'scripts_footer' => $this->buildFooterScripts(),
        ];
    }

    // -----------------------------------------------------------------
    // Flash Messages
    // -----------------------------------------------------------------

    /**
     * Collect one-shot session flash messages for the theme templates.
     *
     * Reads the standard type keys (success, info, warning, danger,
     * error) from the session and hands themes a shaped list so they can
     * render alerts wherever they like in the layout. 'error' normalizes
     * to 'danger' so every type maps onto a CSS class both themes ship.
     * Non-string values and empty messages are dropped.
     *
     * @return list<array{type: string, message: string}>
     */
    protected function buildFlash(): array
    {
        $flash = [];
        $session = $this->app->session();

        foreach (['success', 'info', 'warning', 'danger', 'error'] as $type) {
            if (!$session->hasFlash($type)) {
                continue;
            }

            $message = $session->pullFlash($type);
            if (!is_string($message) || $message === '') {
                continue;
            }

            $flash[] = [
                'type'    => $type === 'error' ? 'danger' : $type,
                'message' => $message,
            ];
        }

        return $flash;
    }

    // -----------------------------------------------------------------
    // Breadcrumbs
    // -----------------------------------------------------------------

    /**
     * Auto-generate breadcrumbs from the current URI segments.
     *
     * The last segment uses the page title from route data if available.
     * Home (/) returns an empty array (no breadcrumbs on the homepage).
     *
     * @param array<string, mixed> $routeData Route data containing optional 'title' or 'archive_title'
     * @return array<array{label: string, url: string|null}>
     */
    protected function buildBreadcrumbs(array $routeData = []): array
    {
        $parsedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = trim(is_string($parsedPath) ? $parsedPath : '', '/');

        if ($uri === '' || $uri === '/') {
            return [];
        }

        $segments = explode('/', $uri);
        $crumbs = [['label' => 'Home', 'url' => '/']];
        $path = '';
        $pageTitle = $routeData['title'] ?? $routeData['archive_title'] ?? null;

        foreach ($segments as $i => $segment) {
            $path .= '/' . $segment;
            $isLast = ($i === count($segments) - 1);
            $label = ($isLast && $pageTitle)
                ? $pageTitle
                : ucwords(str_replace(['-', '_'], ' ', $segment));

            $crumbs[] = [
                'label' => $label,
                'url'   => $isLast ? null : $path,
            ];
        }

        return $crumbs;
    }

    // -----------------------------------------------------------------
    // Theme Options
    // -----------------------------------------------------------------

    /**
     * Get theme options as a nested array (dot-notation expanded).
     *
     * Flat keys like 'hero.show' become ['hero']['show'].
     *
     * @return array<string, mixed> Nested options for the active theme
     */
    protected function getThemeOptions(): array
    {
        $active = $this->app->themes()->getActive();
        if (!$active) {
            return [];
        }

        $flat = $this->app->themes()->getThemeOptions((int) $active->id);
        $nested = [];

        foreach ($flat as $key => $value) {
            $parts = explode('.', $key);
            if (count($parts) === 2) {
                $group = $parts[0];
                if (!isset($nested[$group]) || !is_array($nested[$group])) {
                    $nested[$group] = [];
                }
                $nested[$group][$parts[1]] = $value;
            } else {
                $nested[$key] = $value;
            }
        }

        return $nested;
    }

    /**
     * Build the rendered comment thread for a commentable content item.
     *
     * The controller identifies its item as commentable with a hint in its
     * render data (e.g. ['commentable' => ['type' => 'blog', 'id' => 5]]).
     * PublicController checks whether the Comments plugin is installed and
     * renders the thread, so a content controller never needs to know whether
     * comments is present. The theme places {! comments_html !} wherever it
     * likes in the content section.
     *
     * @param array<string, mixed> $routeData Route data containing an optional 'commentable' hint
     * @return string Rendered comment thread HTML, or '' when unavailable
     */
    protected function buildCommentsHtml(array $routeData = []): string
    {
        $hint = $routeData['commentable'] ?? null;
        if (!is_array($hint) || empty($hint['type']) || empty($hint['id'])) {
            return '';
        }

        try {
            $comments = $this->app->comments();
        } catch (\Throwable) {
            return '';
        }

        if (!$comments->isEnabled()) {
            return '';
        }

        try {
            return $comments->render(
                (string) $hint['type'],
                (int) $hint['id'],
                (bool) ($routeData['allow_comments'] ?? $hint['allow_comments'] ?? true)
            );
        } catch (\Throwable) {
            return '';
        }
    }

    // -----------------------------------------------------------------
    // Navigation
    // -----------------------------------------------------------------

    /**
     * Get a nested navigation tree for a group.
     *
     * Returns an empty array if the navigation service is unavailable
     * or the group has no items.
     *
     * @param string $group Navigation group (e.g. 'primary', 'footer')
     * @return list<\Pubvana\Models\NavigationItem> Nested tree of navigation items with ->children arrays
     */
    protected function getNavigation(string $group = 'primary'): array
    {
        try {
            return $this->app->navigation()->getTree($group);
        } catch (\Throwable) {
            return [];
        }
    }

    // -----------------------------------------------------------------
    // Settings & Identity
    // -----------------------------------------------------------------

    /**
     * Get a CMS setting value from the database/settings store.
     *
     * @param string $key     Setting key (e.g. 'CMS.siteName')
     * @param mixed  $default Fallback value
     * @return mixed
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->app->settings()->get($key, $default);
    }

    /**
     * Get the site name with fallback chain.
     *
     * @return string
     */
    protected function getSiteName(): string
    {
        return $this->app->settings()->get('CMS.siteName')
            ?? $this->app->get('CMS.siteName')
            ?? 'Pubvana';
    }

    /**
     * Get the active theme's folder name.
     *
     * @return string
     */
    protected function getActiveThemeName(): string
    {
        $active = $this->app->themes()->getActive();
        return $active ? $active->folder : 'default';
    }

    // -----------------------------------------------------------------
    // Plugin-Contributed Assets
    // -----------------------------------------------------------------

    /**
     * Collect CSS file paths registered by plugins via public.css adext.
     *
     * Each plugin URL is checked against the active theme for a same-named
     * replacement (see swapPluginCss()); when the theme ships one, the
     * theme's URL is collected and the plugin's file does not load.
     *
     * @return string[] URLs of CSS files to include in <head>
     */
    protected function getPublicCss(): array
    {
        $css = [];
        foreach ($this->app->adext()->get('public.css', 'default') as $entry) {
            $url = $entry['url'] ?? '';
            if ($url !== '') {
                $css[] = $this->swapPluginCss($url);
            }
        }
        return $css;
    }

    /**
     * Point a plugin CSS registration at the theme's replacement file when
     * the theme ships one. Plugin URLs look like
     * `/assets/plugin/{Name}/css/{file}.css`; the expected replacement is
     * `themes/{active}/assets/{Name}/css/{file}.css`, served at
     * `/assets/theme/{active}/{Name}/css/{file}.css`. No replacement means
     * the plugin URL loads as registered.
     *
     * @param string $url A public.css registration URL
     */
    protected function swapPluginCss(string $url): string
    {
        if (preg_match('#^/assets/plugin/([^/]+)/([a-zA-Z0-9/._-]+)$#', $url, $m) !== 1) {
            return $url;
        }
        if (str_contains($m[2], '..')) {
            return $url;
        }

        $view = $this->app->view();
        $themePath = ($view instanceof \Pubvana\Services\PluginView) ? $view->getThemePath() : null;
        if ($themePath === null || $themePath === '') {
            return $url;
        }

        // themePath points at themes/{name}/Views; the swap file sits in the
        // theme root's assets/ for that plugin name.
        $candidate = dirname($themePath)
            . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . $m[1]
            . DIRECTORY_SEPARATOR . $m[2];
        if (!is_file($candidate)) {
            return $url;
        }

        $themeName = basename(dirname($themePath));
        return '/assets/theme/' . $themeName . '/' . $m[1] . '/' . $m[2];
    }

    /**
     * Collect JS file paths registered by plugins via public.js adext.
     *
     * @return string[] URLs of JS files to include before </body>
     */
    protected function getPublicJs(): array
    {
        $js = [];
        foreach ($this->app->adext()->get('public.js', 'default') as $entry) {
            $url = $entry['url'] ?? '';
            if ($url !== '') {
                $js[] = $url;
            }
        }
        return $js;
    }

    /**
     * Assemble plugin scripts into one finished <script> block, the footer
     * counterpart to buildHeadHtml(). Themes output it whole with
     * {! scripts_footer !}; there is nothing left to loop over.
     */
    protected function buildFooterScripts(): string
    {
        $html = '';
        foreach ($this->getPublicJs() as $js) {
            $html .= '<script src="' . htmlspecialchars($js) . '"></script>' . "\n";
        }
        return $html;
    }

    protected function getPublicHead(): string
    {
        $html = '';
        foreach ($this->app->adext()->get('public.head', 'other') as $entry) {
            $html .= ($entry['output'] ?? '') . "\n";
        }
        return $html;
    }

    /**
     * @param array<string, mixed> $header
     */
    protected function buildHeadHtml(array $header): string
    {
        $html = '<title>' . htmlspecialchars($header['title']) . '</title>' . "\n";

        foreach ($header['meta'] as $meta) {
            $html .= '<meta name="' . htmlspecialchars($meta['name']) . '" content="' . htmlspecialchars($meta['content']) . '">' . "\n";
        }

        foreach ($header['og'] as $og) {
            $html .= '<meta property="' . htmlspecialchars($og['property']) . '" content="' . htmlspecialchars($og['content']) . '">' . "\n";
        }

        $html .= $header['seo'];
        $html .= $header['head'];

        foreach ($header['css'] as $css) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($css) . '">' . "\n";
        }

        return $html;
    }
}
