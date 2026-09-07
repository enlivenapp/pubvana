<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Comments\Services;

use Pubvana\Plugins\Comments\Models\Comment;
use flight\Engine;

/**
 * CommentService - Business logic for the Comments plugin.
 *
 * Owns the comment lifecycle: creation (with validation, depth limits,
 * captcha, and HTML sanitization), moderation (approve/reject/delete),
 * listing, and the public host-injection contract that lets other plugins
 * embed a comment thread + form in their own views.
 *
 * Settings are read through the core SettingsService under the "Comments."
 * namespace (e.g. "Comments.comments_enabled"). Values resolve down the
 * declared-default chain until a row is saved through the settings UI.
 *
 * @package Pubvana\Plugins\Comments\Services
 */
class CommentService
{
    private Comment $model;

    /** @var Engine<object> */
    private Engine $app;

    /** @var list<string>|null Cached set of commentable_types owned by hosts the admin has opted in. */
    private ?array $enabledTypesCache = null;

    /**
     * Per-request memoization of the hostTypeMap() call chain. Each registered
     * 'comments.host' callable issues a SELECT to build its commentable
     * catalog; muting repeated call calls stops the same catalog from being
     * re-fetched for every comment render / type lookup. Dies with the request.
     *
     * @var array<string, string>|null type => host key
     */
    private ?array $hostTypeMapCache = null;

    /**
     * @param Engine<object> $app
     */
    public function __construct(\PDO $pdo, Engine $app)
    {
        $this->model = new Comment($pdo);
        $this->app   = $app;
    }

    // -----------------------------------------------------------------
    // Settings
    // -----------------------------------------------------------------

    /**
     * Read a "Comments.*" setting via the core settings service.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->app->settings()->get('Comments.' . $key, $default);
    }

    /**
     * Whether the comment system is globally enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) $this->setting('comments_enabled', true);
    }

    /**
     * Whether guest comments are allowed.
     */
    public function allowsGuestComments(): bool
    {
        return (bool) $this->setting('allow_guest_comments', false);
    }

    /**
     * Default status assigned to new comments.
     */
    public function defaultStatus(): string
    {
        $status = (string) $this->setting('default_status', 'pending');
        return in_array($status, ['pending', 'approved'], true) ? $status : 'pending';
    }

    /**
     * Maximum nesting depth for threaded replies.
     */
    public function maxNestingDepth(): int
    {
        return max(1, (int) $this->setting('max_nesting_depth', 3));
    }

    // -----------------------------------------------------------------
    // Reads
    // -----------------------------------------------------------------

    /**
     * Paginated comment list for admin, optionally filtered by status.
     *
     * @return array<int, Comment>
     */
    public function list(int $page = 1, int $perPage = 25, ?string $status = null): array
    {
        return $this->model->paginate($page, $perPage, $status);
    }

    /**
     * Get a threaded comment tree for a content item (public display).
     *
     * @return array<int, Comment>
     */
    public function findForContent(string $type, int $id): array
    {
        return $this->buildTree($this->model->findByContent($type, $id, 'approved'));
    }

    /**
     * Find a single comment by ID.
     */
    public function find(int $id): ?Comment
    {
        return $this->model->findById($id);
    }

    /**
     * Count comments by status (null = all).
     */
    public function countByStatus(?string $status = null): int
    {
        return $this->model->countByStatus($status);
    }

    // -----------------------------------------------------------------
    // Writes
    // -----------------------------------------------------------------

    /**
     * Create a new comment.
     *
     * Validates nesting depth, verifies captcha, and sanitizes the body.
     * Returns the created Comment.
     *
     * @throws \InvalidArgumentException When validation fails (callers
     *         catch this and surface a user-facing message).
     */
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Comment
    {
        // Validate nesting depth
        if (!empty($data['parent_id'])) {
            $parentId = (int) $data['parent_id'];
            $depth = $this->model->getDepth($parentId);

            if ($depth >= $this->maxNestingDepth()) {
                throw new \InvalidArgumentException('Maximum comment nesting depth reached.');
            }
        }

        // Captcha verification: delegated to the site-wide captcha service.
        // Fails closed when the area is protected but the token is missing
        // or the provider rejects it (including on a missing secret key).
        if ($this->app->captcha()->enforcedFor('comments')) {
            $token = (string) ($data['captcha_token'] ?? '');
            $ip = (string) ($data['ip_address'] ?? '');

            if ($token === '' || !$this->app->captcha()->verify($token, $ip)) {
                throw new \InvalidArgumentException('Captcha verification failed.');
            }
        }

        // Remove non-column fields before insert
        unset($data['captcha_token']);

        // HTMLPurifier - always sanitize
        if (!empty($data['body'])) {
            $data['body'] = $this->purifyContent((string) $data['body']);
        }

        // Set default status from settings
        if (empty($data['status'])) {
            $data['status'] = $this->defaultStatus();
        }

        return $this->model->createRecord($data);
    }

    /**
     * Approve a comment.
     */
    public function approve(int $id): ?Comment
    {
        return $this->model->updateStatus($id, 'approved');
    }

    /**
     * Reject a comment.
     */
    public function reject(int $id): ?Comment
    {
        return $this->model->updateStatus($id, 'rejected');
    }

    /**
     * Hard delete a comment.
     */
    public function delete(int $id): bool
    {
        return $this->model->deleteById($id);
    }

    // -----------------------------------------------------------------
    // Host Injection Contract
    // -----------------------------------------------------------------

    /**
     * Render a comment thread + form for a content item to HTML.
     *
     * This is the injection point for host plugins. A content plugin that
     * has registered itself as a 'comments.host' calls this from its own
     * controller/template and injects the returned HTML wherever comments
     * should appear in its view.
     *
     * Returns an empty string when comments are disabled for the item or
     * the plugin overall.
     *
     * @param string $type          The host's commentable_type token (e.g. 'blog')
     * @param int    $id            The content item id
     * @param bool   $allowComments Whether the item itself allows comments
     * @return string Rendered HTML, or '' when comments are not shown
     */
    public function render(string $type, int $id, bool $allowComments = true): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        if (!in_array($type, $this->enabledTypes(), true)) {
            return '';
        }

        $data = $this->dataFor($type, $id, $allowComments);
        if (empty($data['comments_enabled'])) {
            return '';
        }

        $view = $this->app->view();
        if (!$view instanceof \Pubvana\Services\PluginView) {
            return '';
        }

        // Resolve the .tpl partial through the 3-tier override chain.
        $template = $this->resolveTemplate($view);
        $vision = $template === '' ? null : $view->vision();
        if ($template === '' || $vision === null) {
            return '';
        }

        try {
            return $vision->render($template, $data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Resolve the public comments partial through the 3-tier override chain.
     *
     * Mirrors RegionManager::resolveBlockTemplate: the partial is always a
     * Vision .tpl file, so the extension is appended explicitly rather than
     * relying on PluginView's mutable $extension (which may still be '.php'
     * when a host renders comments before its own page render()).
     */
    private function resolveTemplate(\Pubvana\Services\PluginView $view): string
    {
        $file = 'pubvana/comments/public/comments';

        $parts = explode('/', $file);
        if (count($parts) < 3) {
            return '';
        }

        $packageName = $parts[0] . '/' . $parts[1];
        $relativePath = implode('/', array_slice($parts, 2)) . '.tpl';
        $prefixedPath = $file . '.tpl';

        $appViewsPath = $this->app->get('flight.views.path') ?? PROJECT_ROOT . '/app/Views';
        $appOverride = $appViewsPath . DIRECTORY_SEPARATOR . $prefixedPath;
        if (is_file($appOverride)) {
            return $appOverride;
        }

        $themePath = $view->getThemePath();
        if ($themePath !== null) {
            $themeOverride = $themePath . DIRECTORY_SEPARATOR . $prefixedPath;
            if (is_file($themeOverride)) {
                return $themeOverride;
            }
        }

        $pluginViewPath = $view->getPluginPath($packageName);
        if ($pluginViewPath !== null) {
            $pluginFile = $pluginViewPath . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($pluginFile)) {
                return $pluginFile;
            }
        }

        return '';
    }


    /**
     * Build the full data array a host view needs to render comments.
     *
     * Use this when the host wants to render the thread with its own markup,
     * or when you need the raw values rather than the pre-rendered HTML from
     * render().
     *
     * @return array<string, mixed> Empty when comments are disabled.
     */
    public function dataFor(string $type, int $id, bool $allowComments = true): array
    {
        if (!$this->isEnabled() || !$allowComments) {
            return [];
        }

        if (!in_array($type, $this->enabledTypes(), true)) {
            return [];
        }

        $tree = $this->findForContent($type, $id);
        $comments = $this->flattenComments($tree);

        $userId = function_exists('user_id') ? user_id() : null;
        $isGuest = ($userId === null);
        $commentsOpen = $allowComments && ($userId !== null || $this->allowsGuestComments());
        $commentsClosed = !$allowComments;

        $error = '';
        try {
            $query = $this->app->request()->query->comment_error ?? null;
            if (is_string($query) && $query !== '') {
                $error = $query;
            }
        } catch (\Throwable $e) {
            $error = '';
        }

        return [
            'comments'             => $comments,
            'comments_enabled'     => true,
            'comments_open'        => $commentsOpen,
            'comments_closed'      => $commentsClosed,
            'comments_is_guest'    => $isGuest,
            'comments_error'       => $error,
            'commentable_type'     => $type,
            'commentable_id'       => $id,
            'comment_post_url'     => $this->app->pluginLoader()->routePrefix('pubvana/comments') . '/' . $type . '/' . $id,
            'max_nesting_depth'    => $this->maxNestingDepth(),
            'csrf_field'           => function_exists('csrf_field') ? csrf_field() : '',
        ];
    }

    /**
     * Enumerate all registered comment hosts.
     *
     * Each host registers via adext 'comments.host' with a callable that
     * returns its commentable items. This enriches the recent-comments block
     * so a stored comment can be linked back to its content.
     *
     * @return array<array{type: string, id: int, title: string, url: string, allow_comments: bool}>
     */
    public function hostItems(): array
    {
        $items = [];

        foreach ($this->app->adext()->get('comments.host', 'content') as $hostKey => $contribution) {
            if (!$this->isHostEnabled((string) $hostKey)) {
                continue;
            }
            if (empty($contribution['callable']) || !is_callable($contribution['callable'])) {
                continue;
            }

            try {
                $result = $contribution['callable']();
            } catch (\Throwable $e) {
                continue;
            }

            if (!is_array($result)) {
                continue;
            }

            foreach ($result as $item) {
                if (!is_array($item) || !isset($item['type'], $item['id'])) {
                    continue;
                }

                $items[] = [
                    'type'           => (string) $item['type'],
                    'id'             => (int) $item['id'],
                    'title'          => (string) ($item['title'] ?? $item['label'] ?? ucfirst((string) $item['type']) . ' #' . $item['id']),
                    'url'            => (string) ($item['url'] ?? '#'),
                    'allow_comments' => (bool) ($item['allow_comments'] ?? false),
                ];
            }
        }

        return $items;
    }

    /**
     * Resolve a single host item by type + id (for enriching comment displays).
     *
     * @return array{type: string, id: int, title: string, url: string, allow_comments: bool}|null
     */
    public function hostItem(string $type, int $id): ?array
    {
        foreach ($this->hostItems() as $item) {
            if ($item['type'] === $type && $item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * All registered comment hosts (undelegated, callables intact).
     *
     * @return array<string, array<string, mixed>> Keyed by host key
     */
    public function hosts(): array
    {
        return $this->app->adext()->get('comments.host', 'content') ?: [];
    }

    /**
     * Hosts that accept comments, in display order.
     *
     * @param bool $decorate When true, attach 'enabled' + 'label' to each
     * @return array<string, mixed>
     */
    public function enabledHosts(bool $decorate = true): array
    {
        $hosts   = $this->hosts();
        $enabled = $this->enabledHostKeys();

        $out = [];
        foreach ($hosts as $key => $host) {
            $on = in_array($key, $enabled, true);
            if ($decorate) {
                $host['enabled'] = $on;
                $out[$key] = $host;
            } elseif ($on) {
                $out[$key] = $host;
            }
        }
        return $out;
    }

    /**
     * Whether comments are currently accepted for a given host.
     *
     * Opt-in: a host is enabled ONLY when an admin has explicitly added it
     * to Comments.enabledHosts.
     */
    public function isHostEnabled(string $key): bool
    {
        return in_array($key, $this->enabledHostKeys(), true);
    }

    /**
     * Whether comments are accepted for a given commentable_type.
     */
    public function isTypeEnabled(string $type): bool
    {
        return in_array($type, $this->enabledTypes(), true);
    }

    /**
     * Toggle a host's participation in the comment system.
     *
     * Opt-in: enabling adds the host to Comments.enabledHosts; disabling
     * removes it. Hosts not listed are closed by default.
     */
    public function setHostEnabled(string $key, bool $enabled): void
    {
        $enabledKeys = $this->enabledHostKeys();

        if ($enabled) {
            if (!in_array($key, $enabledKeys, true)) {
                $enabledKeys[] = $key;
            }
        } else {
            $index = array_search($key, $enabledKeys, true);
            if ($index !== false) {
                unset($enabledKeys[$index]);
                $enabledKeys = array_values($enabledKeys);
            }
        }

        $this->app->settings()->set('Comments.enabledHosts', json_encode($enabledKeys));
    }

    /**
     * Keys of hosts the admin has explicitly opted into the comment system.
     *
     * @return string[]
     */
    protected function enabledHostKeys(): array
    {
        $raw = (string) $this->setting('enabledHosts', '[]');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /**
     * Comment counts per host (total and pending), keyed by host key.
     *
     * A host may expose several commentable types via its callable; each
     * host's totals are the sum across all of its commentable types.
     *
     * @return array<string, array{comments: int, pending: int}> keyed by host key
     */
    public function countsByHost(): array
    {
        $typesToHost = $this->hostTypeMap();

        $all     = $this->model->countByType();
        $pending = $this->model->countByType('pending');

        $out = [];
        foreach ($this->hosts() as $hostKey => $contribution) {
            $out[$hostKey] = ['comments' => 0, 'pending' => 0];
        }

        foreach ($typesToHost as $type => $hostKey) {
            $out[$hostKey]['comments'] += (int) ($all[$type] ?? 0);
            $out[$hostKey]['pending'] = ($out[$hostKey]['pending'] ?? 0) + (int) ($pending[$type] ?? 0);
        }

        return $out;
    }

    /**
     * The host key that owns a given commentable_type, or null.
     */
    public function hostKeyForType(string $type): ?string
    {
        return $this->hostTypeMap()[$type] ?? null;
    }

    /**
     * The set of commentable_types owned by hosts the admin has opted in.
     *
     * Computed once per request and cached; used to gate rendering.
     *
     * @return string[]
     */
    public function enabledTypes(): array
    {
        if ($this->enabledTypesCache !== null) {
            return $this->enabledTypesCache;
        }

        $enabled = $this->enabledHostKeys();
        $map      = $this->hostTypeMap();
        $types    = [];

        foreach ($map as $type => $hostKey) {
            if (in_array($hostKey, $enabled, true)) {
                $types[] = $type;
            }
        }

        return $this->enabledTypesCache = $types;
    }

    /**
     * Map each commentable_type to its owning host key.
     *
     * @return array<string, string> commentable_type => host key
     */
    protected function hostTypeMap(): array
    {
        if ($this->hostTypeMapCache !== null) {
            return $this->hostTypeMapCache;
        }

        $map = [];
        foreach ($this->hosts() as $hostKey => $contribution) {
            if (empty($contribution['callable']) || !is_callable($contribution['callable'])) {
                continue;
            }
            try {
                $result = $contribution['callable']();
            } catch (\Throwable $e) {
                continue;
            }
            if (!is_array($result)) {
                continue;
            }
            foreach ($result as $item) {
                if (is_array($item) && !empty($item['type'])) {
                    $map[(string) $item['type']] = (string) $hostKey;
                }
            }
        }
        return $this->hostTypeMapCache = $map;
    }

    // -----------------------------------------------------------------
    // Block Provider
    // -----------------------------------------------------------------

    /**
     * Data provider for the "Recent Comments" block.
     *
     * Returns the most recently approved comments, enriched with the
     * host content title/URL via the registered 'comments.host' entries.
     *
     * @return array{title: string, comments: array<int, array{author: string, post_title: string, url: string, date: ?string}>}
     */
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function recentCommentsBlock(array $options): array
    {
        $count = (int) ($options['count'] ?? 5);
        if ($count < 1) {
            $count = 5;
        }

        $rows = $this->list(1, $count, 'approved');
        $comments = [];

        foreach ($rows as $comment) {
            $authorName = $comment->guest_name ?: 'Anonymous';
            if ($comment->user_id !== null) {
                $user = (new \Enlivenapp\FlightShield\Models\User(\Flight::db()))
                    ->findById((int) $comment->user_id);
                $authorName = $user->username ?? 'Unknown';
            }

            $host = $this->hostItem((string) $comment->commentable_type, (int) $comment->commentable_id);

            $comments[] = [
                'author'     => $authorName,
                'post_title' => $host['title'] ?? ucfirst((string) $comment->commentable_type) . ' #' . $comment->commentable_id,
                'url'        => $host['url'] ?? '#',
                'date'       => $comment->created_at,
            ];
        }

        return [
            'title'    => $options['title'] ?? 'Recent Comments',
            'comments' => $comments,
        ];
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * Sanitize HTML content via HTMLPurifier.
     */
    private function purifyContent(string $html): string
    {
        if (!class_exists(\HTMLPurifier_Config::class)) {
            return $html;
        }
        $config = \HTMLPurifier_Config::create(\Flight::get('html_purifier') ?? []);
        return (new \HTMLPurifier($config))->purify($html);
    }

    /**
     * Build a nested tree from a flat array of comments.
     */
    /**
     * @param array<int, Comment> $comments
     * @return array<int, Comment>
     */
    private function buildTree(array $comments): array
    {
        $map = [];
        $tree = [];

        // Index all comments by ID
        foreach ($comments as $comment) {
            $map[$comment->id] = $comment;
            $comment->setCustomData('children', []);
        }

        // Build the tree
        foreach ($comments as $comment) {
            if ($comment->parent_id !== null && isset($map[$comment->parent_id])) {
                $parent = $map[$comment->parent_id];
                $children = $parent->children;
                $children[] = $comment;
                $parent->setCustomData('children', $children);
            } else {
                $tree[] = $comment;
            }
        }

        return $tree;
    }

    /**
     * Flatten a nested comment tree into a depth-annotated, template-ready array.
     */
    /**
     * @param array<int, Comment> $tree
     * @return list<array<string, mixed>>
     */
    private function flattenComments(array $tree, int $depth = 0): array
    {
        $flat = [];

        foreach ($tree as $comment) {
            $authorName = $comment->guest_name ?: 'Anonymous';
            if ($comment->user_id !== null) {
                $user = (new \Enlivenapp\FlightShield\Models\User(\Flight::db()))
                    ->findById((int) $comment->user_id);
                $authorName = $user->username ?? 'Unknown';
            }

            $flat[] = [
                'id'         => (int) $comment->id,
                'parent_id'  => $comment->parent_id !== null ? (int) $comment->parent_id : null,
                'depth'      => $depth,
                'author'     => $authorName,
                'body'       => $comment->body,
                'created_at' => $comment->created_at,
                'children'   => !empty($comment->children) ? count($comment->children) : 0,
            ];

            if (!empty($comment->children)) {
                $flat = array_merge($flat, $this->flattenComments($comment->children, $depth + 1));
            }
        }

        return $flat;
    }
}
