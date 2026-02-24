<?php

namespace App\Interfaces;

interface PluginInterface
{
    /**
     * Human-readable name, e.g. 'E-commerce'.
     */
    public function getName(): string;

    /**
     * URL-safe identifier, e.g. 'ecommerce'.
     */
    public function getSlug(): string;

    /**
     * Semantic version string, e.g. '1.0.0'.
     */
    public function getVersion(): string;

    /**
     * Admin sidebar menu items to expose.
     *
     * Each item is an associative array with keys:
     *   'label' => string   (e.g. 'Products')
     *   'url'   => string   (e.g. '/admin/products')
     *   'icon'  => string   (e.g. 'fa-box')
     *
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public function getMenuItems(): array;

    /**
     * Called once per request (cached by PluginManager).
     * Hook into services, events, or register routes here.
     */
    public function register(): void;
}
