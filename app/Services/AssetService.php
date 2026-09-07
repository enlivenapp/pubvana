<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * AssetService - Unified asset serving for themes, plugins, and vendor packages.
 *
 * Resolves asset paths to actual files, validates security, and serves files
 * with proper MIME types and caching headers. Eliminates the need to copy
 * assets to public/ directories.
 *
 * @package Pubvana\Services
 */
class AssetService
{

    /** @var Engine<object>|null The FlightPHP app instance (absent on the early asset path) */
    protected ?Engine $app;

    /** @var string[] Allowed asset types */
    protected array $allowedTypes = ['plugin', 'theme', 'vendor'];

    /** @var string[] Allowed file extensions */
    protected array $allowedExtensions = [
        'css', 'js', 'json',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
    ];

    /** @var array<string, string> MIME type mapping */
    protected array $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'webp'  => 'image/webp',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'otf'   => 'font/otf',
    ];

    /**
     * The Flight engine is optional: this service resolves and streams files
     * from disk and never touches the framework, so the early asset path
     * (asset-server.php) constructs it without booting the app. When an app
     * is present, 404s go through halt(); without one, a bare 404 is sent.
     *
     * @param Engine<object>|null $app
     */
    public function __construct(?Engine $app = null)
    {
        $this->app = $app;
    }

    /**
     * Send a 404 through the app when one exists, else a bare 404.
     */
    private function fail404(): void
    {
        if ($this->app !== null) {
            $this->app->halt(404, 'Asset not found');
            return;
        }

        http_response_code(404);
        exit;
    }

    /**
     * Resolve an asset path to an absolute file path.
     *
     * @param string $type Asset type (plugin, theme, vendor)
     * @param string $name Plugin/theme name or vendor/package
     * @param string $path Relative path within assets directory
     * @return string|null Absolute file path or null if not found/invalid
     */
    public function resolve(string $type, string $name, string $path): ?string
    {
        // Validate type
        if (!in_array($type, $this->allowedTypes, true)) {
            return null;
        }

        // Validate extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            return null;
        }

        // Sanitize inputs (prevent directory traversal)
        $path = str_replace(['../', '..\\'], '', $path);

        // Build file path based on type
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
        $filePath = null;

        switch ($type) {
            case 'plugin':
                // $name is a single component (plugin id); strip any path
                $filePath = $root . '/plugins/' . basename($name) . '/assets/' . $path;
                break;

            case 'theme':
                // $name is a single component (theme id); strip any path
                $filePath = $root . '/themes/' . basename($name) . '/assets/' . $path;
                break;

            case 'vendor':
                // Vendor packages: name is "vendor/package"
                $parts = explode('/', $name, 2);
                if (count($parts) !== 2) {
                    return null;
                }
                // Strip any path from each component to prevent traversal
                $vendor = basename($parts[0]);
                $package = basename($parts[1]);
                $filePath = $root . '/vendor/' . $vendor . '/' . $package . '/assets/' . $path;
                break;
        }

        if ($filePath === null) {
            return null;
        }

        // Validate file exists and is readable
        if (!is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        // Security check: ensure resolved path is within expected directory
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return null;
        }

        $allowedBase = realpath(dirname($filePath, 3)); // Go up to assets/ parent
        if ($allowedBase === false || !str_starts_with($realPath, $allowedBase)) {
            return null;
        }

        return $realPath;
    }

    /**
     * Get MIME type for a file path.
     */
    public function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $this->mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Serve a file with proper headers.
     *
     * @param string $filePath Absolute file path
     * @return void
     */
    public function serve(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->fail404();
            return;
        }

        // Asset responses are binary streams; Tracy's debug bar cannot and
        // should not inject into them (it throws when Content-Length is set).
        if (class_exists(\Tracy\Debugger::class)) {
            \Tracy\Debugger::$showBar = false;
        }

        $mimeType = $this->getMimeType($filePath);
        $lastModified = filemtime($filePath);
        if ($lastModified === false) {
            $this->fail404();
            return;
        }
        $etag = md5($filePath . $lastModified);

        // Check If-None-Match (ETag)
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if ($ifNoneMatch === $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }

        // Set headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: public, max-age=86400'); // 1 day
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

        readfile($filePath);
        exit;
    }
}
