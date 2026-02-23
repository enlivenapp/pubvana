<?php

/**
 * Pubvana CMS Helper Functions.
 * Loaded globally via Config/Autoload.php $helpers.
 */

use App\Services\ThemeService;
use App\Services\WidgetService;

if (! function_exists('active_theme')) {
    function active_theme(): ?object
    {
        return (new ThemeService())->getActive();
    }
}

if (! function_exists('widget_area')) {
    function widget_area(string $slug): string
    {
        return (new WidgetService())->renderArea($slug);
    }
}

if (! function_exists('theme_url')) {
    function theme_url(string $path = ''): string
    {
        $theme = active_theme();
        if (! $theme) {
            return base_url('themes/default/assets/' . ltrim($path, '/'));
        }
        return base_url('themes/' . $theme->folder . '/' . ltrim($path, '/'));
    }
}

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return (string) (setting('App.siteName') ?? 'Pubvana');
    }
}

if (! function_exists('site_tagline')) {
    function site_tagline(): string
    {
        return (string) (setting('App.siteTagline') ?? '');
    }
}

if (! function_exists('post_url')) {
    function post_url(string $slug): string
    {
        return base_url('blog/' . $slug);
    }
}

if (! function_exists('category_url')) {
    function category_url(string $slug): string
    {
        return base_url('category/' . $slug);
    }
}

if (! function_exists('tag_url')) {
    function tag_url(string $slug): string
    {
        return base_url('tag/' . $slug);
    }
}

if (! function_exists('render_content')) {
    function render_content(object $entity): string
    {
        if (isset($entity->content_type) && $entity->content_type === 'markdown') {
            return (new \Parsedown())->text($entity->content ?? '');
        }
        return $entity->content ?? '';
    }
}

if (! function_exists('excerpt')) {
    function excerpt(string $text, int $length = 150): string
    {
        $plain = strip_tags($text);
        if (strlen($plain) <= $length) {
            return $plain;
        }
        return rtrim(substr($plain, 0, $length), ' .,;:') . '…';
    }
}

if (! function_exists('slug_from_title')) {
    function slug_from_title(string $title): string
    {
        return url_title($title, '-', true);
    }
}

if (! function_exists('theme_view')) {
    /**
     * Render a view file from the active theme using direct PHP include.
     * Avoids CI4's view() absolute-path restriction.
     */
    function theme_view(string $path, array $data = []): string
    {
        if (! is_file($path)) {
            return '';
        }
        extract($data);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
