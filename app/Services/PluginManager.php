<?php

namespace App\Services;

use App\Interfaces\PluginInterface;

class PluginManager
{
    private static ?self $instance = null;

    /** @var PluginInterface[] */
    private array $plugins = [];

    private bool $loaded = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Discover and load all plugins from plugins/{name}/Plugin.php.
     * Safe to call multiple times — only runs once per request.
     */
    public function loadAll(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        foreach (glob(ROOTPATH . 'plugins/*/Plugin.php') as $pluginFile) {
            require_once $pluginFile;

            // Derive class name from directory: plugins/ecommerce/Plugin.php → EcommercePlugin
            $dir       = basename(dirname($pluginFile));
            $className = ucfirst($dir) . 'Plugin';

            if (class_exists($className) && is_subclass_of($className, PluginInterface::class)) {
                /** @var PluginInterface $plugin */
                $plugin = new $className();
                $plugin->register();
                $this->plugins[$plugin->getSlug()] = $plugin;
            }
        }
    }

    /**
     * Merged admin sidebar menu items from all loaded plugins.
     *
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public function getMenuItems(): array
    {
        $items = [];
        foreach ($this->plugins as $plugin) {
            foreach ($plugin->getMenuItems() as $item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * All loaded plugin instances.
     *
     * @return PluginInterface[]
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }
}
