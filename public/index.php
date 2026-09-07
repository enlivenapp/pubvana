<?php

/**
 * Pubvana CMS - Entry Point
 *
 * This is the front controller. All requests hit this file via Apache/Nginx
 * rewrite rules. It loads the bootstrap which handles autoloading, config,
 * services, routes, and starts FlightPHP.
 *
 * Asset requests short-circuit before the framework bootstrap: they are
 * static files resolved from disk by AssetService and need no session,
 * database, plugin loading, or route table.
 *
 * @package Pubvana
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI !== 'cli' && str_starts_with((string) $requestPath, '/assets/')) {
    require(__DIR__ . '/../app/config/asset-server.php');
    exit;
}

$ds = DIRECTORY_SEPARATOR;
require(__DIR__ . $ds . '..' . $ds . 'app' . $ds . 'config' . $ds . 'bootstrap.php');
