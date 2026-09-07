<?php

declare(strict_types=1);

namespace Pubvana\Plugins\ActivityLog\Services;

use Pubvana\Plugins\ActivityLog\Models\ActivityLog;
use flight\Engine;
use flight\net\Route;

/**
 * ActivityLogService - Core service for the Activity Log plugin.
 *
 * Provides explicit logging API and auto-tracking from admin routes.
 *
 * @package Pubvana\Plugins\ActivityLog\Services
 */
class ActivityLogService
{
    private \PDO $pdo;
    /** @var array<string, mixed> */
    private array $config;
    /** @var \flight\Engine<object>|null */
    private $app = null;

    /**
     * @param \PDO $pdo
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /**
     * Set the app instance (for accessing auth, request, etc.)
     * @param \flight\Engine<object> $app
     */
    public function setApp($app): void
    {
        $this->app = $app;
    }

    /**
     * Explicit log entry.
     *
     * @param array<string, mixed> $data
     *   - action: string (required) - create, update, delete, publish, settings_change, etc.
     *   - entity_type: string (required) - blog_post, page, redirect, user, setting, form, etc.
     *   - entity_id: int|null
     *   - entity_name: string (required) - human-readable name
     *   - details: array|null - additional context, will be JSON-encoded
     *   - user_id: int|null - defaults to current authenticated user
     *   - user_name: string|null - defaults to current authenticated user name
     *   - ip: string|null - defaults to current request IP
     *   - user_agent: string|null - defaults to current request user agent
     */
    public function log(array $data): void
    {
        if ($this->app === null) {
            return;
        }

        try {
            $user = $this->app->auth()->user();
            $request = $this->app->request();

            $log = new ActivityLog($this->pdo);
            $log->user_id = $data['user_id'] ?? ($user->id ?? null);
            $log->user_name = $data['user_name'] ?? ($user->username ?? 'system');
            $log->action = $data['action'] ?? 'unknown';
            $log->entity_type = $data['entity_type'] ?? 'unknown';
            $log->entity_id = $data['entity_id'] ?? null;
            $log->entity_name = $data['entity_name'] ?? '';
            $encoded = isset($data['details']) ? json_encode($data['details']) : null;
            $log->details = $encoded === false ? null : $encoded;
            $log->ip = $data['ip'] ?? $this->getClientIp($request);
            $log->user_agent = $data['user_agent'] ?? $this->getUserAgent($request);
            $log->created_at = date('Y-m-d H:i:s');
            $log->save();
        } catch (\Throwable $e) {
            error_log('ActivityLog: failed to write log entry: ' . $e->getMessage());
        }
    }

    /**
     * Auto-extract log data from an admin route.
     */
    public function logFromRoute(Route $route): void
    {
        if ($this->app === null) {
            return;
        }

        // Check if tracking is enabled
        if (($this->config['track_admin_actions'] ?? true) === false) {
            return;
        }

        // Skip non-mutating methods. Route has no $method property (it is
        // $methods, an array), so read the actual request method.
        $method = $this->app->request()->method;
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }

        // Skip non-admin routes
        $pattern = $route->pattern ?? '';
        if (!str_starts_with($pattern, '/admin/')) {
            return;
        }

        // Skip certain routes (login, logout, asset endpoints, etc.)
        if ($this->shouldSkipRoute($pattern)) {
            return;
        }

        // Infer action and entity from route
        $inferred = $this->inferFromRoute($pattern, $method);
        if ($inferred === null) {
            return;
        }

        $params = $route->params;

        $this->log([
            'action'      => $inferred['action'],
            'entity_type' => $inferred['entity_type'],
            'entity_id'   => $this->extractEntityId($params),
            'entity_name' => $this->extractEntityName($params, $inferred['entity_type']),
            'details'     => ['route' => $pattern, 'method' => $method, 'params' => $params],
        ]);
    }

    /**
     * List log entries with filtering and pagination.
     *
     * @param array<string, mixed> $filters
     * @return array<int, ActivityLog>
     */
    public function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $model = new ActivityLog($this->pdo);
        return $model->filtered($filters, $page, $perPage);
    }

    /**
     * Count filtered log entries.
     *
     * @param array<string, mixed> $filters
     */
    public function count(array $filters = []): int
    {
        $model = new ActivityLog($this->pdo);
        return $model->countFiltered($filters);
    }

    /**
     * Get count of entries in the last 24 hours (for dashboard card).
     */
    public function countRecent24h(): int
    {
        $model = new ActivityLog($this->pdo);
        $since = date('Y-m-d H:i:s', strtotime('-24 hours'));
        return $model->countSince($since);
    }

    /**
     * Get distinct actions for filter dropdown.
     *
     * @return string[]
     */
    public function getActions(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct entity types for filter dropdown.
     *
     * @return string[]
     */
    public function getEntityTypes(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type");
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get distinct users for filter dropdown.
     *
     * @return array<int, array{user_id: int, user_name: string}>
     */
    public function getUsers(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT user_id, user_name FROM activity_logs WHERE user_id IS NOT NULL ORDER BY user_name"
        );
        if ($stmt === false) {
            return [];
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Determine if a route should be skipped from auto-tracking.
     */
    private function shouldSkipRoute(string $pattern): bool
    {
        $skipPatterns = [
            '/admin/auth/',        // login/logout
            '/admin/assets/',      // asset serving
            '/admin/api/',         // API endpoints
            '/admin' . rtrim((string) ($this->config['route_prefix'] ?? ''), '/'), // self-reference
        ];

        foreach ($skipPatterns as $skip) {
            if (str_starts_with($pattern, $skip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer action and entity type from route pattern.
     *
     * @return array{action: string, entity_type: string, entity_id: int|null, entity_name: string}|null
     */
    private function inferFromRoute(string $pattern, string $method): ?array
    {
        // Strip the /admin prefix, keep the leading slash (map keys are /-prefixed)
        $path = substr($pattern, strlen('/admin'));

        // Common patterns
        $patterns = [
            // Blog
            '/blog/posts'              => ['entity_type' => 'blog_post', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
            '/blog/categories'         => ['entity_type' => 'blog_category', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
            '/blog/tags'               => ['entity_type' => 'blog_tag', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Pages
            '/pages'                   => ['entity_type' => 'page', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Media
            '/media'                   => ['entity_type' => 'media', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Redirects (the 404 manager key must come first: matching is
            // str_starts_with, so the longer path wins)
            '/redirects/404-manager'   => ['entity_type' => 'redirect_link', 'actions' => ['update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
            '/redirects'               => ['entity_type' => 'redirect', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Forms
            '/forms'                   => ['entity_type' => 'form', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
            '/form-submissions'        => ['entity_type' => 'form_submission', 'actions' => ['delete' => 'DELETE']],

            // Users/Groups/Permissions
            '/users'                   => ['entity_type' => 'user', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE', 'toggle' => 'POST']],
            '/groups'                  => ['entity_type' => 'group', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
            '/permissions'             => ['entity_type' => 'permission', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Settings
            '/settings'                => ['entity_type' => 'setting', 'actions' => ['update' => ['PUT', 'PATCH', 'POST']]],

            // Navigation
            '/navigation'              => ['entity_type' => 'navigation_item', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],

            // Themes/Regions
            '/themes'                  => ['entity_type' => 'theme', 'actions' => ['update' => ['PUT', 'PATCH'], 'activate' => 'POST']],
            '/regions'                 => ['entity_type' => 'region', 'actions' => ['update' => ['PUT', 'PATCH']]],

            // Plugins
            '/plugins'                 => ['entity_type' => 'plugin', 'actions' => ['toggle' => 'POST']],

            // SEO
            '/seo'                     => ['entity_type' => 'seo', 'actions' => ['update' => ['PUT', 'PATCH', 'POST']]],

            // Comments
            '/comments'                => ['entity_type' => 'comment', 'actions' => ['update' => ['PUT', 'PATCH'], 'delete' => 'DELETE', 'approve' => 'POST', 'reject' => 'POST']],

            // Profiles
            '/profiles'                => ['entity_type' => 'profile', 'actions' => ['update' => ['PUT', 'PATCH']]],

            // Backups
            '/backups'                 => ['entity_type' => 'backup', 'actions' => ['create' => 'POST', 'delete' => 'DELETE', 'restore' => 'POST']],

            // Analytics
            '/analytics'               => ['entity_type' => 'analytics', 'actions' => ['toggle' => 'POST']],

            // Social Links
            '/social-links'            => ['entity_type' => 'social_link', 'actions' => ['create' => 'POST', 'update' => ['PUT', 'PATCH'], 'delete' => 'DELETE']],
        ];

        foreach ($patterns as $routePrefix => $config) {
            if (str_starts_with($path, $routePrefix)) {
                $action = $this->matchAction($method, $config['actions'], $path);
                if ($action !== null) {
                    return [
                        'action'      => $action,
                        'entity_type' => $config['entity_type'],
                        'entity_id'   => null,
                        'entity_name' => '',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Match HTTP method and path to an action.
     *
     * @param array<string, string|string[]> $actions
     */
    private function matchAction(string $method, array $actions, string $path): ?string
    {
        foreach ($actions as $action => $methods) {
            $methodList = is_array($methods) ? $methods : [$methods];
            if (in_array($method, $methodList, true)) {
                // Check for specific action in path (e.g., /toggle, /activate)
                if ($action !== 'create' && $action !== 'update' && $action !== 'delete') {
                    if (str_contains($path, '/' . $action)) {
                        return $action;
                    }
                }
                return $action;
            }
        }
        return null;
    }

    /**
     * Extract entity ID from route params.
     *
     * Flight's Route::$params PHPDoc claims int keys, but matchUrl() fills
     * named params under string keys. Accept either so both are provable.
     *
     * @param array<int|string, string|null> $params
     */
    private function extractEntityId(array $params): ?int
    {
        foreach (['id', 'entity_id', 'post_id', 'page_id', 'redirect_id', 'form_id', 'user_id', 'group_id', 'permission_id', 'comment_id', 'media_id', 'category_id', 'tag_id', 'submission_id'] as $key) {
            if (isset($params[$key]) && is_numeric($params[$key])) {
                return (int) $params[$key];
            }
        }
        return null;
    }

    /**
     * Extract entity name from route params.
     *
     * @param array<int|string, string|null> $params
     */
    private function extractEntityName(array $params, string $entityType): string
    {
        foreach (['name', 'title', 'slug', 'label', 'source_path', 'email', 'username'] as $key) {
            if (isset($params[$key]) && $params[$key] !== '') {
                return $params[$key];
            }
        }
        return $entityType;
    }

    /**
     * Get client IP from request.
     *
     * @param \flight\net\Request $request
     */
    private function getClientIp($request): string
    {
        $forwarded = $request->getHeader('X-Forwarded-For');
        if ($forwarded !== '') {
            $ips = explode(',', $forwarded);
            return trim($ips[0]);
        }
        $realIp = $request->getHeader('X-Real-IP');
        if ($realIp !== '') {
            return $realIp;
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get user agent from request.
     *
     * @param \flight\net\Request $request
     */
    private function getUserAgent($request): string
    {
        $ua = $request->getHeader('User-Agent');
        return $ua !== '' ? $ua : 'unknown';
    }
}