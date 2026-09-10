<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Api;

use flight\Engine;

/**
 * ApiBaseController - Base controller for sessionless /api/* JSON endpoints.
 *
 * Machine-facing controllers (sessionless, JSON, bearer/query-token auth)
 * extend this instead of PublicController, which carries the page layout
 * machinery they never use. This class owns only the shared HTTP plumbing:
 * emitting JSON, reading the auth header, and reading the request body,
 * method, and path. Response shapes stay with the child controllers because
 * remote callers depend on each plugin's existing envelope.
 *
 * @package Pubvana\Controllers\Api
 */
abstract class ApiBaseController
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

    // -----------------------------------------------------------------
    // Responses
    // -----------------------------------------------------------------

    /**
     * Emit a JSON response without halting the request.
     *
     * @param array<mixed>|object $data
     */
    protected function json(mixed $data, int $status = 200): void
    {
        $this->app->json($data, $status);
    }

    /**
     * Emit a JSON response and halt the request.
     *
     * @param array<mixed>|object $data
     */
    protected function jsonHalt(mixed $data, int $status = 200): never
    {
        $this->app->jsonHalt($data, $status);
    }

    // -----------------------------------------------------------------
    // Request
    // -----------------------------------------------------------------

    /**
     * The bearer token from the Authorization header, or null when absent.
     *
     * Falls back to getallheaders() because Apache/mod_php does not always
     * expose Authorization in $_SERVER['HTTP_AUTHORIZATION'].
     */
    protected function bearerToken(): ?string
    {
        $header = $this->app->request()->getHeader('Authorization');

        if ($header === '' && function_exists('getallheaders')) {
            $all = getallheaders();
            $header = (string) ($all['Authorization'] ?? $all['authorization'] ?? '');
        }

        if (preg_match('/^Bearer\s+(\S+)$/i', trim($header), $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function method(): string
    {
        return $this->app->request()->method;
    }

    protected function path(): string
    {
        $url = $this->app->request()->url ?? '/';
        return (string) parse_url($url, PHP_URL_PATH);
    }

    /**
     * Decoded JSON request body, or [] when absent or unreadable.
     *
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        try {
            return $this->app->request()->data->getData();
        } catch (\Throwable) {
            return [];
        }
    }

    // -----------------------------------------------------------------
    // Plugin context
    // -----------------------------------------------------------------

    /**
     * The plugin's /api/* route prefix.
     */
    protected function apiPrefix(string $pluginId): string
    {
        return $this->app->pluginLoader()->apiPrefix($pluginId);
    }

    /**
     * Get a config value for this plugin.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->app->get($this->configPrepend . '.' . $key) ?? $default;
    }
}