<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * PluginViewContextMiddleware - Sets the active plugin before each controller.
 *
 * When a plugin's routes are loaded by the PluginLoader, each route group
 * is wrapped with this middleware. Before the controller runs, it sets the
 * active plugin on PluginView so un-prefixed render() calls resolve to
 * the correct plugin's views.
 *
 * Example: A blog controller calls render('post') and this middleware
 * ensures it resolves to plugins/blog/Views/post.php (or the theme
 * override, or the owner override).
 *
 * After the controller runs, the context is cleared so the next request
 * doesn't accidentally inherit it.
 *
 * @package Pubvana\Services
 */
class PluginViewContextMiddleware
{
    /** @var Engine<object> The FlightPHP app instance */
    protected Engine $app;

    /** @var string Plugin ID to set as active context */
    protected string $pluginId;

    /**
     * @param Engine<object> $app      The FlightPHP app instance
     * @param string         $pluginId Plugin ID (e.g. 'pubvana/blog')
     */
    public function __construct(Engine $app, string $pluginId)
    {
        $this->app = $app;
        $this->pluginId = $pluginId;
    }

    /**
     * Set the active plugin context before the controller runs.
     *
     * Also syncs the theme override tier with the active theme from the
     * database: services.php keys themePath off the 'active_theme' config
     * key, which nothing sets, so it would stay 'default' (see
     * PublicController::render for the same sync on public pages). Without
     * the sync here, theme overrides for plugin views resolve against the
     * wrong theme whenever the active theme is not the default one.
     *
     * @return void
     */
    public function before(): void
    {
        $view = $this->app->view();
        if (!$view instanceof PluginView) {
            return;
        }

        $view->setCurrentPlugin($this->pluginId);

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
            // Themes table missing on fresh installs; boot already placed
            // a fallback theme path.
        }
    }

    /**
     * Clear the active plugin context after the controller runs.
     *
     * @return void
     */
    public function after(): void
    {
        $view = $this->app->view();
        if ($view instanceof PluginView) {
            $view->setCurrentPlugin(null);
        }
    }
}
