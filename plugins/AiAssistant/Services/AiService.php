<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Services;

use Pubvana\Plugins\AiAssistant\Models\AiKey;
use Pubvana\Plugins\AiAssistant\Models\AiKeyGrant;
use Pubvana\Plugins\AiAssistant\Models\AiLog;
use Pubvana\Plugins\Blog\Models\Post;
use flight\Engine;

/**
 * AiService - API key management, bearer authentication, grants, and
 * the audit log for the AI Assistant plugin.
 *
 * Registered on the app engine as `ai` by the plugin (Plugin::register),
 * reachable as `$app->ai()`. Reads its tuning knobs from the plugin's
 * Config/Config.php (key_prefix, max_failed_attempts, block_minutes,
 * log_limit).
 *
 * Security model:
 * - Keys are random, high-entropy tokens. Only an HMAC-SHA256 hash is
 *   stored (keyed with a domain key derived from SESSION_ENCRYPTION_KEY,
 *   mirroring the Mailer's key-derivation approach), so a leaked database
 *   cannot be used to mint or replay tokens.
 * - Grants are deny-all. A key with no grants can authenticate but cannot
 *   do anything else.
 * - Repeated use of a disabled key is counted; crossing the threshold
 *   blocks the key for block_minutes as defense-in-depth.
 * - Every request is written to ai_logs with its outcome (ok/denied/error),
 *   including unauthenticated attempts.
 *
 * @package Pubvana\Plugins\AiAssistant\Services
 */
class AiService
{
    private \PDO $pdo;

    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    private ?string $domainKey = null;

    /** @var array<int, array<string, int>> Cached grant sets per key id (permission => original index) */
    private array $grantsCache = [];

    /**
     * @param Engine<object> $app
     * @param array<string, mixed> $config
     */
    public function __construct(\PDO $pdo, Engine $app, array $config = [])
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->config = $config;
    }

    // -----------------------------------------------------------------
    // Key Management
    // -----------------------------------------------------------------

    /**
     * All keys with their grants, newest first, as display-safe arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listKeys(): array
    {
        $keys = $this->model()->allOrdered();

        $result = [];
        foreach ($keys as $key) {
            $result[] = [
                'id'              => (int) $key->id,
                'name'            => (string) $key->name,
                'key_prefix'      => (string) $key->key_prefix,
                'enabled'         => $key->isEnabled(),
                'blocked'         => $key->isBlocked(),
                'failed_attempts' => (int) $key->failed_attempts,
                'blocked_until'   => $key->blocked_until !== null ? (string) $key->blocked_until : null,
                'last_used_at'    => $key->last_used_at !== null ? (string) $key->last_used_at : null,
                'created_at'      => (string) $key->created_at,
                'grants'          => $this->grantModel()->permissionsFor((int) $key->id),
            ];
        }

        return $result;
    }

    public function findKey(int $id): ?AiKey
    {
        return $this->model()->findById($id);
    }

    /**
     * Create a key and return the plaintext token exactly once.
     *
     * @param string $name Human label for the key
     * @return array{key: AiKey, plain: string}
     */
    public function createKey(string $name): array
    {
        $plain = $this->generateToken();
        $now = $this->now();

        $key = $this->model();
        $key->name = $this->normalizeName($name);
        $key->key_hash = $this->hashToken($plain);
        $key->key_prefix = substr($plain, 0, 16);
        $key->enabled = 1;
        $key->failed_attempts = 0;
        $key->blocked_until = null;
        $key->last_used_at = null;
        $key->created_at = $now;
        $key->updated_at = $now;
        $key->insert();

        return ['key' => $key, 'plain' => $plain];
    }

    /**
     * Replace a key's entire grant set.
     *
     * @param int      $keyId
     * @param string[] $permissions
     * @return bool False when the key does not exist
     */
    public function updateGrants(int $keyId, array $permissions): bool
    {
        $key = $this->findKey($keyId);
        if ($key === null) {
            return false;
        }

        $catalog = $this->helpCatalog();
        $cleaned = [];
        foreach ($permissions as $permission) {
            $permission = (string) $permission;
            if (isset($catalog[$permission])) {
                $cleaned[] = $permission;
            }
        }

        $this->grantModel()->replaceFor($keyId, $cleaned);
        unset($this->grantsCache[$keyId]);
        return true;
    }

    /**
     * Toggle a key's enabled state.
     *
     * @return bool False when the key does not exist
     */
    public function toggle(int $keyId): bool
    {
        $key = $this->findKey($keyId);
        if ($key === null) {
            return false;
        }

        $key->enabled = $key->isEnabled() ? 0 : 1;
        $key->updated_at = $this->now();
        $key->save();
        return true;
    }

    /**
     * Delete a key and all of its grants.
     *
     * @return bool False when the key does not exist
     */
    public function deleteKey(int $keyId): bool
    {
        $key = $this->findKey($keyId);
        if ($key === null) {
            return false;
        }

        $this->grantModel()->replaceFor($keyId, []);
        $key->delete();
        unset($this->grantsCache[$keyId]);
        return true;
    }

    // -----------------------------------------------------------------
    // Authentication
    // -----------------------------------------------------------------

    /**
     * Authenticate a raw bearer token.
     *
     * @param string $token Raw token from the Authorization header
     * @return array{key: ?AiKey, error: ?string, key_known?: AiKey} key is null on failure
     */
    public function authenticate(string $token): array
    {
        $hash = $this->hashToken($token);
        $key = $this->model()->findByHash($hash);

        if ($key === null) {
            return ['key' => null, 'error' => 'Invalid API key.'];
        }

        if (!$key->isEnabled()) {
            // Probing a disabled key is an attributable failure: count it
            // and block the key once the threshold is crossed.
            $this->recordFailure($key);
            return ['key' => null, 'error' => 'This API key is disabled.', 'key_known' => $key];
        }

        if ($key->isBlocked()) {
            return ['key' => null, 'error' => 'This API key is temporarily blocked due to repeated failed attempts.', 'key_known' => $key];
        }

        $this->resetFailures($key);
        $this->markUsed($key);

        return ['key' => $key, 'error' => null];
    }

    /**
     * Whether a key holds a given grant.
     */
    public function hasGrant(AiKey $key, string $permission): bool
    {
        $keyId = (int) $key->id;
        if (!isset($this->grantsCache[$keyId])) {
            $this->grantsCache[$keyId] = array_flip($this->grantModel()->permissionsFor($keyId));
        }
        return isset($this->grantsCache[$keyId][$permission]);
    }

    // -----------------------------------------------------------------
    // Audit Log
    // -----------------------------------------------------------------

    /**
     * Append one audit entry for a request to the /api/ai/* API.
     *
     * Tolerant by design: if the table is absent (fresh install before
     * migrations run) the attempt is logged to error_log instead and the
     * request continues.
     */
    public function log(string $method, string $endpoint, string $outcome, ?AiKey $key = null, ?string $entityType = null, ?int $entityId = null, ?string $detail = null): void
    {
        $outcome = in_array($outcome, ['ok', 'denied', 'error'], true) ? $outcome : 'error';

        try {
            $entry = new AiLog($this->pdo);
            $entry->key_id = $key !== null ? (int) $key->id : null;
            $entry->key_name = $key !== null ? (string) $key->name : null;
            $entry->method = $method;
            $entry->endpoint = $endpoint;
            $entry->entity_type = $entityType;
            $entry->entity_id = $entityId;
            $entry->outcome = $outcome;
            $entry->detail = $detail;
            $entry->ip = $this->clientIp();
            $entry->created_at = $this->now();
            $entry->insert();
        } catch (\Throwable $e) {
            error_log('AiService: unable to write audit log (' . $e->getMessage() . ')');
        }
    }

    /**
     * Recent audit entries as display-safe arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentLogs(int $limit): array
    {
        $logs = (new AiLog($this->pdo))->recent(max(1, $limit));

        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'id'          => (int) $log->id,
                'key_id'      => $log->key_id !== null ? (int) $log->key_id : null,
                'key_name'    => $log->key_name !== null ? (string) $log->key_name : null,
                'method'      => (string) $log->method,
                'endpoint'    => (string) $log->endpoint,
                'entity_type' => $log->entity_type !== null ? (string) $log->entity_type : null,
                'entity_id'   => $log->entity_id !== null ? (int) $log->entity_id : null,
                'outcome'     => (string) $log->outcome,
                'detail'      => $log->detail !== null ? (string) $log->detail : null,
                'ip'          => $log->ip !== null ? (string) $log->ip : null,
                'created_at'  => (string) $log->created_at,
            ];
        }

        return $rows;
    }

    // -----------------------------------------------------------------
    // Settings
    // -----------------------------------------------------------------

    /**
     * The user AI-created posts are attributed to.
     */
    public function defaultAuthorId(): int
    {
        $id = (int) $this->app->settings()->get('Ai.default_author_id', 1);
        return $id > 0 ? $id : 1;
    }

    public function setDefaultAuthorId(int $id): void
    {
        $this->app->settings()->set('Ai.default_author_id', $id > 0 ? $id : 1);
    }

    // -----------------------------------------------------------------
    // Help / Grant Catalog
    // -----------------------------------------------------------------

    /**
     * The complete grant catalog: permission => route/label/description.
     *
     * Also drives /api/ai/help, the admin Help page, and grant-form rendering,
     * so the catalog is a single source of truth. `label` is the plain
     * English name shown to site admins; `summary` is the one-liner.
     *
     * @return array<string, array{method: string, path: string, group: string, label: string, summary: string}>
     */
    public function helpCatalog(): array
    {
        $base = $this->apiBase();

        return [
            'posts.read'             => ['method' => 'GET',  'path' => $base . '/posts/{slug}',          'group' => 'posts',      'label' => 'View posts',           'summary' => 'List and view posts. Lists show every status and support page, status, and search params.', 'endpoints' => [$base . '/posts', $base . '/posts/{slug}']],
            'posts.create'           => ['method' => 'POST', 'path' => $base . '/posts',                 'group' => 'posts',      'label' => 'Create drafts',        'summary' => 'Write a new blog post as a draft.'],
            'posts.update'           => ['method' => 'POST', 'path' => $base . '/posts/{id}/update',     'group' => 'posts',      'label' => 'Edit posts',           'summary' => 'Change an existing post.'],
            'posts.delete'           => ['method' => 'POST', 'path' => $base . '/posts/{id}/delete',     'group' => 'posts',      'label' => 'Delete posts',         'summary' => 'Remove a post.'],
            'posts.publish'          => ['method' => 'POST', 'path' => $base . '/posts',                 'group' => 'posts',      'label' => 'Publish posts',        'summary' => 'Put a post live on the site.'],
            'posts.schedule'         => ['method' => 'POST', 'path' => $base . '/posts',                 'group' => 'posts',      'label' => 'Schedule posts',       'summary' => 'Set a future publish date for a post.'],
            'posts.tags.read'        => ['method' => 'GET',  'path' => $base . '/posts/tags',            'group' => 'posts',      'label' => 'View tags',            'summary' => 'Read the list of blog tags.'],
            'posts.categories.read'  => ['method' => 'GET',  'path' => $base . '/posts/categories',      'group' => 'posts',      'label' => 'View categories',      'summary' => 'Read the list of blog categories.'],
            'pages.read'             => ['method' => 'GET',  'path' => $base . '/pages/{slug}',          'group' => 'pages',      'label' => 'View pages',           'summary' => 'List and view pages. Lists show every status and support page, status, and search params.', 'endpoints' => [$base . '/pages', $base . '/pages/{slug}']],
            'pages.create'           => ['method' => 'POST', 'path' => $base . '/pages',                 'group' => 'pages',      'label' => 'Create page drafts',   'summary' => 'Write a new page as a draft.'],
            'pages.update'           => ['method' => 'POST', 'path' => $base . '/pages/{id}/update',     'group' => 'pages',      'label' => 'Edit pages',           'summary' => 'Change an existing page.'],
            'pages.delete'           => ['method' => 'POST', 'path' => $base . '/pages/{id}/delete',     'group' => 'pages',      'label' => 'Delete pages',         'summary' => 'Remove a page.'],
            'pages.publish'          => ['method' => 'POST', 'path' => $base . '/pages',                 'group' => 'pages',      'label' => 'Publish pages',        'summary' => 'Put a page live on the site.'],
            'comments.read'          => ['method' => 'GET',  'path' => $base . '/comments',              'group' => 'comments',   'label' => 'View comments',        'summary' => 'See visitor comments.'],
            'comments.approve'       => ['method' => 'POST', 'path' => $base . '/comments/{id}/approve', 'group' => 'comments',   'label' => 'Approve comments',     'summary' => 'Allow a pending comment to show.'],
            'comments.reject'        => ['method' => 'POST', 'path' => $base . '/comments/{id}/reject',  'group' => 'comments',   'label' => 'Reject comments',      'summary' => 'Disallow a pending comment.'],
            'comments.delete'        => ['method' => 'POST', 'path' => $base . '/comments/{id}/delete',  'group' => 'comments',   'label' => 'Delete comments',      'summary' => 'Remove a comment permanently.'],
            'redirects.read'         => ['method' => 'GET',  'path' => $base . '/redirects',             'group' => 'redirects',  'label' => 'View redirects',       'summary' => 'See the site\'s URL redirect rules.'],
            'redirects.create'       => ['method' => 'POST', 'path' => $base . '/redirects',             'group' => 'redirects',  'label' => 'Create redirects',     'summary' => 'Add a URL redirect rule.'],
            'redirects.update'       => ['method' => 'POST', 'path' => $base . '/redirects/{id}/update', 'group' => 'redirects',  'label' => 'Edit redirects',       'summary' => 'Change a redirect rule.'],
            'redirects.delete'       => ['method' => 'POST', 'path' => $base . '/redirects/{id}/delete', 'group' => 'redirects',  'label' => 'Delete redirects',     'summary' => 'Remove a redirect rule.'],
            'navigation.read'        => ['method' => 'GET',  'path' => $base . '/navigation',            'group' => 'navigation', 'label' => 'View navigation',      'summary' => 'See the site\'s menu items.'],
            'navigation.create'      => ['method' => 'POST', 'path' => $base . '/navigation',            'group' => 'navigation', 'label' => 'Add menu items',        'summary' => 'Create a navigation menu item.'],
            'navigation.update'      => ['method' => 'POST', 'path' => $base . '/navigation/{id}/update','group' => 'navigation', 'label' => 'Edit menu items',      'summary' => 'Change a navigation menu item.'],
            'navigation.delete'      => ['method' => 'POST', 'path' => $base . '/navigation/{id}/delete','group' => 'navigation', 'label' => 'Delete menu items',      'summary' => 'Remove a navigation menu item.'],
        ];
    }

    /**
     * URL prefix for the public API, from the plugin config.
     */
    private function apiBase(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }

    /**
     * Catalog grouped by resource, in display order.
     *
     * @return array<string, array<string, array{method: string, path: string, summary: string}>>
     */
    public function helpGroups(): array
    {
        $groups = [];
        foreach ($this->helpCatalog() as $permission => $entry) {
            $groups[$entry['group']][$permission] = $entry;
        }
        return $groups;
    }

    // -----------------------------------------------------------------
    // Content Lists
    // -----------------------------------------------------------------

    /**
     * Paginated post list for the API.
     *
     * Lists every post regardless of status (so an AI key holder can find
     * drafts they are working on). An optional status filter and a search
     * term across title, slug, excerpt, and content are applied when given.
     * Items are light: they carry status and an optional content snippet,
     * but not the full body. Fetch a single post for full content.
     *
     * @param int         $page
     * @param int         $perPage
     * @param string|null $status
     * @param string|null $search
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, status: string|null, search: string|null}
     */
    public function listPostsForApi(int $page = 1, int $perPage = 25, ?string $status = null, ?string $search = null): array
    {
        $query = new Post($this->pdo);
        $this->applyListFilters($query, $search);
        if ($status !== null) {
            $query->eq('status', $status);
        }

        $rows = $query->isNull('deleted_at')
            ->order('id DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();

        $items = [];
        foreach ($rows as $post) {
            $items[] = $this->serializePostListItem($post, $search);
        }

        return [
            'items'    => $items,
            'total'    => $this->countPostsForApi($status, $search),
            'page'     => $page,
            'per_page' => $perPage,
            'status'   => $status,
            'search'   => $search,
        ];
    }

    /**
     * Paginated page list for the API.
     *
     * Same behavior as listPostsForApi, applied to pages.
     *
     * @param int         $page
     * @param int         $perPage
     * @param string|null $status
     * @param string|null $search
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, status: string|null, search: string|null}
     */
    public function listPagesForApi(int $page = 1, int $perPage = 25, ?string $status = null, ?string $search = null): array
    {
        $query = new \Pubvana\Plugins\Pages\Models\Page($this->pdo);
        $this->applyListFilters($query, $search, false);
        if ($status !== null) {
            $query->eq('status', $status);
        }

        $rows = $query->isNull('deleted_at')
            ->order('created_at DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->findAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->serializePageListItem($row, $search);
        }

        return [
            'items'    => $items,
            'total'    => $this->countPagesForApi($status, $search),
            'page'     => $page,
            'per_page' => $perPage,
            'status'   => $status,
            'search'   => $search,
        ];
    }

    /**
     * Apply the search term as an OR-wrapped LIKE across searchable fields.
     *
     * @param \Pubvana\Plugins\Blog\Models\Post|\Pubvana\Plugins\Pages\Models\Page $query  Active record query builder
     * @param string|null  $search
     * @param bool         $includeExcerpt  Posts also search the excerpt
     */
    private function applyListFilters($query, ?string $search, bool $includeExcerpt = true): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $query->startWrap()
            ->like('title', '%' . $search . '%')
            ->like('slug', '%' . $search . '%', 'or')
            ->like('content', '%' . $search . '%', 'or');
        if ($includeExcerpt) {
            $query->like('excerpt', '%' . $search . '%', 'or');
        }
        $query->endWrap('OR');
    }

    private function countPostsForApi(?string $status, ?string $search): int
    {
        $query = (new Post($this->pdo))->select('COUNT(*) as cnt');
        $this->applyListFilters($query, $search);
        if ($status !== null) {
            $query->eq('status', $status);
        }
        $result = $query->isNull('deleted_at')->find();
        return (int) ($result->cnt ?? 0);
    }

    private function countPagesForApi(?string $status, ?string $search): int
    {
        $query = (new \Pubvana\Plugins\Pages\Models\Page($this->pdo))->select('COUNT(*) as cnt');
        $this->applyListFilters($query, $search, false);
        if ($status !== null) {
            $query->eq('status', $status);
        }
        $result = $query->isNull('deleted_at')->find();
        return (int) ($result->cnt ?? 0);
    }

    /**
     * Light post row for lists: metadata and status, never the full body.
     *
     * @param Post        $post
     * @param string|null $search Term used to build the content snippet
     * @return array<string, mixed>
     */
    private function serializePostListItem(Post $post, ?string $search): array
    {
        return [
            'id'             => (int) $post->id,
            'title'          => (string) $post->title,
            'slug'           => (string) $post->slug,
            'excerpt'        => $post->excerpt !== null ? (string) $post->excerpt : null,
            'status'         => (string) $post->status,
            'published_at'   => $post->published_at !== null ? (string) $post->published_at : null,
            'author_id'      => (int) $post->author_id,
            'allow_comments' => (int) $post->allow_comments === 1,
            'updated_at'     => (string) $post->updated_at,
            'snippet'        => $search !== null && $search !== '' ? $this->contentSnippet([$post->content, $post->excerpt], $search) : null,
        ];
    }

    /**
     * Light page row for lists: metadata and status, never the full body.
     *
     * @param \Pubvana\Plugins\Pages\Models\Page $page
     * @param string|null $search Term used to build the content snippet
     * @return array<string, mixed>
     */
    private function serializePageListItem($page, ?string $search): array
    {
        return [
            'id'             => (int) $page->id,
            'title'          => (string) $page->title,
            'slug'           => (string) $page->slug,
            'status'         => (string) $page->status,
            'created_by'     => (int) $page->created_by,
            'allow_comments' => (int) $page->allow_comments === 1,
            'updated_at'     => $page->updated_at !== null ? (string) $page->updated_at : null,
            'snippet'        => $search !== null && $search !== '' ? $this->contentSnippet([$page->content], $search) : null,
        ];
    }

    /**
     * Plain-text excerpt around the first match of a search term.
     *
     * Sources are checked in order; the first one that contains the term
     * provides the surrounding text, so the snippet shows what matched.
     * When nothing matches, the head of the first non-empty source is used.
     * Mirrors the technique used by the Pages model's searchContent.
     *
     * @param array<int, string|null> $sources Raw HTML/excerpt candidates
     * @param string                  $term
     * @return string
     */
    private function contentSnippet(array $sources, string $term): string
    {
        $fallback = '';
        foreach ($sources as $source) {
            $text = $this->snippetText($source);
            if ($text === '') {
                continue;
            }
            $len = mb_strlen($text);
            $pos = mb_stripos($text, $term);
            if ($pos !== false) {
                $start = max(0, $pos - 80);
                return ($start > 0 ? '...' : '') . mb_substr($text, $start, 200) . ($start + 200 < $len ? '...' : '');
            }
            if ($fallback === '') {
                $fallback = mb_substr($text, 0, 200) . ($len > 200 ? '...' : '');
            }
        }
        return $fallback;
    }

    /**
     * Strip a raw HTML source to collapsed plain text.
     */
    private function snippetText(?string $source): string
    {
        $text = html_entity_decode(strip_tags((string) $source), ENT_QUOTES, 'UTF-8');
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    /**
     * Stored HTML converted to Markdown for API reads.
     */
    private function htmlToMarkdown(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return null;
        }
        return $this->app->aiMarkdown()->toMarkdown($html);
    }

    // -----------------------------------------------------------------
    // Navigation Update
    // -----------------------------------------------------------------

    /**
     * Update a navigation item by id.
     *
     * NavigationService intentionally has no update method, so the plugin
     * works on the NavigationItem model directly to honour admin-side
     * update semantics.
     *
     * @param int                  $id
     * @param array<string, mixed> $data Allowed: label, url, nav_group, parent_id, sort_order, target
     * @return array<string, mixed>|null Normalized item, or null when not found
     */
    public function updateNavigationItem(int $id, array $data): ?array
    {
        $model = new \Pubvana\Models\NavigationItem($this->pdo);
        $item = $model->findById($id);
        if ($item === null) {
            return null;
        }

        if (array_key_exists('label', $data)) {
            $item->label = (string) $data['label'];
        }
        if (array_key_exists('url', $data)) {
            $item->url = (string) $data['url'];
        }
        if (array_key_exists('nav_group', $data)) {
            $item->nav_group = (string) $data['nav_group'];
        }
        if (array_key_exists('parent_id', $data)) {
            $item->parent_id = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        }
        if (array_key_exists('sort_order', $data)) {
            $item->sort_order = (int) $data['sort_order'];
        }
        if (array_key_exists('target', $data)) {
            $item->target = (string) $data['target'];
        }

        $item->updated_at = $this->now();
        $item->save();

        return $this->serializeNavigationItem($item);
    }

    /**
     * Get all navigation items grouped by group, flattened and ordered.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function listNavigationByGroup(): array
    {
        $navigation = $this->app->navigation();
        $groups = $navigation->getGroups();

        $result = [];
        foreach ($groups as $group) {
            foreach ($navigation->getByGroup($group) as $item) {
                $result[$group][] = $this->serializeNavigationItem($item);
            }
        }

        return $result;
    }

    // -----------------------------------------------------------------
    // Serializers
    // -----------------------------------------------------------------

    /**
     * @param \Pubvana\Plugins\Blog\Models\Post $post
     * @return array<string, mixed>
     */
    public function serializePost($post): array
    {
        return [
            'id'               => (int) $post->id,
            'title'            => (string) $post->title,
            'slug'             => (string) $post->slug,
            'content'          => $this->htmlToMarkdown($post->content),
            'excerpt'          => $post->excerpt !== null ? (string) $post->excerpt : null,
            'status'           => (string) $post->status,
            'published_at'     => $post->published_at !== null ? (string) $post->published_at : null,
            'author_id'        => (int) $post->author_id,
            'views'            => (int) $post->views,
            'is_featured'      => (int) $post->is_featured === 1,
            'allow_comments'   => (int) $post->allow_comments === 1,
            'ai_generated'     => (int) $post->ai_generated === 1,
            'seo'              => $this->seoBlock('post', (int) $post->id),
            'created_at'       => (string) $post->created_at,
            'updated_at'       => (string) $post->updated_at,
        ];
    }

    /**
     * @param \Pubvana\Plugins\Pages\Models\Page $page
     * @return array<string, mixed>
     */
    public function serializePage($page): array
    {
        return [
            'id'               => (int) $page->id,
            'title'            => (string) $page->title,
            'slug'             => (string) $page->slug,
            'content'          => $this->htmlToMarkdown($page->content),
            'status'           => (string) $page->status,
            'allow_comments'   => (int) $page->allow_comments === 1,
            'ai_generated'     => (int) $page->ai_generated === 1,
            'seo'              => $this->seoBlock('page', (int) $page->id),
            'created_by'       => (int) $page->created_by,
            'created_at'       => $page->created_at !== null ? (string) $page->created_at : null,
            'updated_at'       => $page->updated_at !== null ? (string) $page->updated_at : null,
        ];
    }

    /**
     * Read back a content item's SEO metadata for API responses.
     *
     * Returns null when the item has no seo_meta row or the SEO plugin is
     * disabled. Mirrors the nested "seo" object accepted on writes so the
     * assistant can round-trip the same shape.
     *
     * @return array<string, mixed>|null
     */
    protected function seoBlock(string $contentType, int $contentId): ?array
    {
        try {
            $seo = $this->app->seo();
        } catch (\Throwable) {
            return null;
        }

        $meta = $seo->getMeta($contentType, $contentId);
        if ($meta === null) {
            return null;
        }

        return [
            'meta_title'       => $meta->meta_title !== null ? (string) $meta->meta_title : null,
            'meta_description' => $meta->meta_description !== null ? (string) $meta->meta_description : null,
            'canonical_url'    => $meta->canonical_url !== null ? (string) $meta->canonical_url : null,
            'robots_directive' => $meta->robots_directive !== null ? (string) $meta->robots_directive : null,
            'focus_keywords'   => $meta->getFocusKeywordsArray(),
            'og_title'         => $meta->og_title !== null ? (string) $meta->og_title : null,
            'og_description'   => $meta->og_description !== null ? (string) $meta->og_description : null,
            'og_image'         => $meta->og_image !== null ? (string) $meta->og_image : null,
            'og_type'          => $meta->og_type !== null ? (string) $meta->og_type : null,
            'twitter_card'     => $meta->twitter_card !== null ? (string) $meta->twitter_card : null,
            'hreflang'         => $meta->hreflang !== null ? (string) $meta->hreflang : null,
        ];
    }

    /**
     * @param \Pubvana\Plugins\Comments\Models\Comment $comment
     * @return array<string, mixed>
     */
    public function serializeComment($comment): array
    {
        return [
            'id'                => (int) $comment->id,
            'commentable_type'  => (string) $comment->commentable_type,
            'commentable_id'    => (int) $comment->commentable_id,
            'parent_id'         => $comment->parent_id !== null ? (int) $comment->parent_id : null,
            'author_type'       => $comment->user_id !== null ? 'user' : 'guest',
            'author_id'         => $comment->user_id !== null ? (int) $comment->user_id : null,
            'author_name'       => $comment->user_id !== null
                ? ($this->userName((int) $comment->user_id) ?? $comment->guest_name ?? 'Unknown')
                : (string) ($comment->guest_name ?? 'Anonymous'),
            'body'              => (string) $comment->body,
            'status'            => (string) $comment->status,
            'created_at'        => $comment->created_at !== null ? (string) $comment->created_at : null,
        ];
    }

    /**
     * @param \Pubvana\Plugins\Redirects\Models\Redirect $redirect
     * @return array<string, mixed>
     */
    public function serializeRedirect($redirect): array
    {
        return [
            'id'            => (int) $redirect->id,
            'source_path'   => (string) $redirect->source_path,
            'target_url'    => (string) $redirect->target_url,
            'status_code'   => (int) $redirect->status_code,
            'enabled'       => (int) $redirect->enabled === 1,
            'notes'         => $redirect->notes !== null ? (string) $redirect->notes : null,
            'hit_count'     => (int) $redirect->hit_count,
            'last_hit_at'   => $redirect->last_hit_at !== null ? (string) $redirect->last_hit_at : null,
            'created_at'    => (string) $redirect->created_at,
            'updated_at'    => (string) $redirect->updated_at,
        ];
    }

    /**
     * @param \Pubvana\Models\NavigationItem $item
     * @return array<string, mixed>
     */
    public function serializeNavigationItem($item): array
    {
        return [
            'id'         => (int) $item->id,
            'label'      => (string) $item->label,
            'url'        => (string) $item->url,
            'parent_id'  => $item->parent_id !== null ? (int) $item->parent_id : null,
            'sort_order' => (int) $item->sort_order,
            'target'     => (string) $item->target,
            'nav_group'  => (string) $item->nav_group,
            'updated_at' => $item->updated_at !== null ? (string) $item->updated_at : null,
        ];
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * Generate a raw API token: prefix + 64 hex chars (256 bits).
     */
    protected function generateToken(): string
    {
        $prefix = (string) ($this->config['key_prefix'] ?? 'pvai1_');
        return $prefix . bin2hex(random_bytes(32));
    }

    /**
     * Hash a raw token to its stored key_hash. Keyed with a domain key
     * derived from SESSION_ENCRYPTION_KEY, mirroring the Mailer's
     * encryptionKey()/cipherKey() derivation.
     */
    protected function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, $this->domainKey());
    }

    /**
     * Domain-separated HMAC key derived from SESSION_ENCRYPTION_KEY.
     */
    protected function domainKey(): string
    {
        if ($this->domainKey === null) {
            $envKey = $_ENV['SESSION_ENCRYPTION_KEY'] ?? (getenv('SESSION_ENCRYPTION_KEY') ?: null);
            if (!is_string($envKey) || $envKey === '') {
                throw new \RuntimeException('AiService: SESSION_ENCRYPTION_KEY is not available for API key hashing.');
            }
            $this->domainKey = hash_hmac('sha256', 'pubvana.ai.v1', $envKey, true);
        }
        return $this->domainKey;
    }

    /**
     * Count a failed attempt and block the key once the threshold is met.
     */
    protected function recordFailure(AiKey $key): void
    {
        $threshold = max(1, (int) ($this->config['max_failed_attempts'] ?? 3));
        $minutes = max(1, (int) ($this->config['block_minutes'] ?? 30));

        $key->failed_attempts = (int) $key->failed_attempts + 1;
        if ((int) $key->failed_attempts >= $threshold) {
            $key->blocked_until = date('Y-m-d H:i:s', time() + ($minutes * 60));
            $key->failed_attempts = 0;
        }
        $key->updated_at = $this->now();
        $key->save();
    }

    /**
     * Clear a key's failure state after a successful authentication.
     */
    protected function resetFailures(AiKey $key): void
    {
        if ((int) $key->failed_attempts === 0 && $key->blocked_until === null) {
            return;
        }
        $key->failed_attempts = 0;
        $key->blocked_until = null;
        $key->updated_at = $this->now();
        $key->save();
    }

    /**
     * Stamp a successful authentication.
     */
    protected function markUsed(AiKey $key): void
    {
        $key->last_used_at = $this->now();
        $key->updated_at = $this->now();
        $key->save();
    }

    /**
     * @return string|null Username for a user id, or null when unavailable.
     */
    protected function userName(int $userId): ?string
    {
        try {
            $user = (new \Enlivenapp\FlightShield\Models\User($this->pdo))->findById($userId);
            return $user !== null ? (string) ($user->username ?? '') : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Untitled key';
        }
        return mb_substr($name, 0, 120);
    }

    protected function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    protected function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    protected function model(): AiKey
    {
        return new AiKey($this->pdo);
    }

    protected function grantModel(): AiKeyGrant
    {
        return new AiKeyGrant($this->pdo);
    }
}