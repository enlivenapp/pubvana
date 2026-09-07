<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Plugins\AiAssistant\Models\AiKey;

/**
 * AiApiController - Sessionless REST endpoints under /ai/*.
 *
 * Every endpoint authenticates the caller with a bearer API key and runs
 * each request through the audit log. Grants are deny-all: an ungranted
 * action is a hard HTTP failure. Responses use the envelope
 * {status, data, errors}.
 *
 * Markdown is converted to HTML at ingest via the aiMarkdown service
 * (league/commonmark + HTMLPurifier). AI-created posts are attributed to
 * the default author setting (Ai.default_author_id), defaulting to user 1.
 *
 * @package Pubvana\Plugins\AiAssistant\Controllers
 */
class AiApiController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.ai');
    }

    // -----------------------------------------------------------------
    // Help
    // -----------------------------------------------------------------

    public function help(): void
    {
        $helpPath = '/' . $this->getRoutePrepend() . '/help';
        $token = $this->bearerToken();
        if ($token === null) {
            // No key sent: the guide is where new clients start, so the
            // failure hints at how to get set up (steps in errors[0].next).
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, 'Missing bearer token.');
            $this->app->jsonHalt([
                'status' => 'error',
                'data'   => null,
                'errors' => [[
                    'code'    => 401,
                    'message' => 'Missing Authentication header. Send the key as "Authorization: Bearer <key>".',
                    'next'    => [
                        'Have a site admin create an API key under Tools > AI Assistant.',
                        'Ask the admin to tick the permissions the assistant is allowed to use.',
                        'Then call GET ' . $helpPath . ' again with the key to receive the full guide.',
                    ],
                ]],
            ], 401);
        }

        $key = $this->requireKey();

        $permissions = array_keys($this->app->ai()->helpCatalog());
        $available = [];
        foreach ($permissions as $permission) {
            if ($this->app->ai()->hasGrant($key, $permission)) {
                $available[] = $permission;
            }
        }

        // /ai/help doubles as the interactive grant guide, but presumably
        // callers hold more grants than the catalog shows by default. List
        // everything so the caller can request grants precisely.
        $this->log($key, 'ok', null, null, 'Grant guide requested.');
        $this->ok([
            'guide'      => $this->app->ai()->helpGroups(),
            'grants_held' => $available,
            'envelope'   => 'All responses use {status, data, errors}.',
            'auth'       => 'Send the API key as "Authorization: Bearer <key>".',
        ]);
    }

    public function helpPermission(string $permission): void
    {
        $key = $this->requireKey();

        $catalog = $this->app->ai()->helpCatalog();
        if (!isset($catalog[$permission])) {
            $this->log($key, 'error', null, null, "Unknown permission '{$permission}'.");
            $this->fail(404, "Unknown permission '{$permission}'. See GET /" . $this->getRoutePrepend() . '/help for the catalog.');
        }

        $entry = $catalog[$permission];
        $entry['granted'] = $this->app->ai()->hasGrant($key, $permission);

        $this->log($key, 'ok', null, null, "Help requested for '{$permission}'.");
        $this->ok($entry);
    }

    // -----------------------------------------------------------------
    // Posts
    // -----------------------------------------------------------------

    public function posts(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $this->log($key, 'error', 'post', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: draft, published, scheduled.');
        }
        $search = $this->searchParam($query);

        $result = $this->app->ai()->listPostsForApi(
            $page,
            $perPage,
            $status !== null ? (string) $status : null,
            $search
        );

        $detail = 'Listed posts';
        if ($status !== null) {
            $detail .= " (status: {$status})";
        }
        if ($search !== null) {
            $detail .= " (search: {$search})";
        }
        $this->log($key, 'ok', 'post', null, $detail . '.');
        $this->ok($result);
    }

    public function post(string $slug): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.read');

        $post = $this->svc('blog')->findPostBySlug($slug);
        if ($post === null) {
            $this->log($key, 'error', 'post', null, "Post '{$slug}' not found.");
            $this->fail(404, 'Post not found.');
        }

        $this->log($key, 'ok', 'post', (int) $post->id, "Fetched post '{$slug}'.");
        $this->ok($this->app->ai()->serializePost($post));
    }

    public function createPost(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.create');

        $payload = $this->payload();

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $this->log($key, 'error', 'post', null, 'Missing title.');
            $this->fail(422, 'title is required.');
        }

        $content = $this->resolveContent($payload);
        if ($content === null) {
            $this->log($key, 'error', 'post', null, 'Missing content.');
            $this->fail(422, 'Provide content_md (markdown) or content (HTML).');
        }

        [$status, $publishedAt] = $this->resolvePostStatus($key, $payload, null);

        $slug = $this->app->slugify((string) ($payload['slug'] ?? '') ?: $title);
        if ($slug === '') {
            $this->log($key, 'error', 'post', null, 'Slug could not be generated.');
            $this->fail(422, 'A URL slug could not be generated from the title.');
        }
        if ($this->svc('blog')->postSlugExists($slug)) {
            $slug .= '-' . time();
        }

        $tags = (string) ($payload['tags'] ?? '');
        if (is_array($payload['tags'] ?? null)) {
            $tags = implode(', ', array_map('strval', (array) $payload['tags']));
        }
        $categories = $this->categoryIds($payload['categories'] ?? []);

        try {
            $post = $this->svc('blog')->createPost([
                'title'            => $title,
                'slug'             => $slug,
                'content'          => $content,
                'excerpt'          => $this->nullableString($payload['excerpt'] ?? null),
                'status'           => $status,
                'featured_image'   => $this->nullableString($payload['featured_image'] ?? null),
                'media_id'         => !empty($payload['media_id']) ? (int) $payload['media_id'] : null,
                'published_at'     => $publishedAt,
                'is_featured'      => !empty($payload['is_featured']) ? 1 : 0,
                'allow_comments'   => !empty($payload['allow_comments']) ? 1 : 0,
                'ai_generated'     => 1,
                'purify_content'   => true,
            ], $this->app->ai()->defaultAuthorId());

            if ($categories !== []) {
                $this->svc('blog')->syncPostCategories((int) $post->id, $categories);
            }
            if ($tags !== '') {
                $this->svc('blog')->syncPostTags((int) $post->id, $tags);
            }
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'post', null, $e->getMessage());
            $this->fail(500, 'The post could not be created: ' . $e->getMessage());
        }

        $this->saveSeo('post', (int) $post->id, $payload);

        $this->log($key, 'ok', 'post', (int) $post->id, "Created post #{$post->id}.");
        $this->ok([
            'post'  => $this->app->ai()->serializePost($post),
            'url'   => $this->app->pluginLoader()->routePrefix('pubvana/blog') . '/' . $slug,
            'id'    => (int) $post->id,
        ]);
    }

    public function updatePost(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.update');

        $payload = $this->payload();
        $blog = $this->svc('blog');

        $existing = $blog->findPost((int) $id);
        if ($existing === null) {
            $this->log($key, 'error', 'post', (int) $id, 'Post not found.');
            $this->fail(404, 'Post not found.');
        }

        $status = (string) ($payload['status'] ?? (string) $existing->status);
        [$status, $publishedAt] = $this->resolvePostStatus($key, $payload, $existing);

        $title = array_key_exists('title', $payload) ? (string) $payload['title'] : (string) $existing->title;
        if (trim($title) === '') {
            $this->log($key, 'error', 'post', (int) $id, 'Empty title.');
            $this->fail(422, 'title must not be empty.');
        }

        $content = $this->resolveContent($payload);
        if ($content === null) {
            $content = (string) ($existing->content ?? '');
        }

        $update = [
            'title'        => $title,
            'content'      => $content,
            'status'       => $status,
            'published_at' => $publishedAt,
        ];
        if (array_key_exists('excerpt', $payload)) {
            $update['excerpt'] = $this->nullableString($payload['excerpt'] ?? null);
        }
        if (array_key_exists('featured_image', $payload)) {
            $update['featured_image'] = $this->nullableString($payload['featured_image'] ?? null);
        }
        if (array_key_exists('media_id', $payload)) {
            $update['media_id'] = !empty($payload['media_id']) ? (int) $payload['media_id'] : null;
        }
        if (array_key_exists('is_featured', $payload)) {
            $update['is_featured'] = !empty($payload['is_featured']) ? 1 : 0;
        }
        if (array_key_exists('allow_comments', $payload)) {
            $update['allow_comments'] = !empty($payload['allow_comments']) ? 1 : 0;
        }
        $update['purify_content'] = true;

        try {
            $post = $blog->updatePost((int) $id, $update, $this->app->ai()->defaultAuthorId());

            if (array_key_exists('categories', $payload)) {
                $blog->syncPostCategories((int) $id, $this->categoryIds($payload['categories']));
            }
            if (array_key_exists('tags', $payload)) {
                $tags = $payload['tags'];
                if (is_array($tags)) {
                    $tags = implode(', ', array_map('strval', $tags));
                }
                $blog->syncPostTags((int) $id, (string) $tags);
            }
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'post', (int) $id, $e->getMessage());
            $this->fail(500, 'The post could not be updated: ' . $e->getMessage());
        }

        $this->saveSeo('post', (int) $id, $payload);

        $this->log($key, 'ok', 'post', (int) $id, "Updated post #{$id}.");
        $this->ok($this->app->ai()->serializePost($post));
    }

    public function deletePost(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.delete');

        $post = $this->svc('blog')->findPost((int) $id);
        if ($post === null) {
            $this->log($key, 'error', 'post', (int) $id, 'Post not found.');
            $this->fail(404, 'Post not found.');
        }

        $this->svc('blog')->deletePost((int) $id);
        $this->log($key, 'ok', 'post', (int) $id, "Deleted post #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    public function tags(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.tags.read');

        $tags = [];
        foreach ($this->svc('blog')->listTags() as $tag) {
            $tags[] = [
                'id'   => (int) $tag->id,
                'name' => (string) $tag->name,
                'slug' => (string) $tag->slug,
            ];
        }

        $this->log($key, 'ok', 'tag', null, 'Listed tags.');
        $this->ok($tags);
    }

    public function categories(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'posts.categories.read');

        $categories = [];
        foreach ($this->svc('blog')->listCategories() as $category) {
            $categories[] = [
                'id'          => (int) $category->id,
                'name'        => (string) $category->name,
                'slug'        => (string) $category->slug,
                'parent_id'   => $category->parent_id !== null ? (int) $category->parent_id : null,
            ];
        }

        $this->log($key, 'ok', 'category', null, 'Listed categories.');
        $this->ok($categories);
    }

    // -----------------------------------------------------------------
    // Pages
    // -----------------------------------------------------------------

    public function pages(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['draft', 'published'], true)) {
            $this->log($key, 'error', 'page', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: draft, published.');
        }
        $search = $this->searchParam($query);

        $result = $this->app->ai()->listPagesForApi(
            $page,
            $perPage,
            $status !== null ? (string) $status : null,
            $search
        );

        $detail = 'Listed pages';
        if ($status !== null) {
            $detail .= " (status: {$status})";
        }
        if ($search !== null) {
            $detail .= " (search: {$search})";
        }
        $this->log($key, 'ok', 'page', null, $detail . '.');
        $this->ok($result);
    }

    public function page(string $slug): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.read');

        $page = $this->svc('pages')->findPageBySlug($slug);
        if ($page === null) {
            $this->log($key, 'error', 'page', null, "Page '{$slug}' not found.");
            $this->fail(404, 'Page not found.');
        }

        $this->log($key, 'ok', 'page', (int) $page->id, "Fetched page '{$slug}'.");
        $this->ok($this->app->ai()->serializePage($page));
    }

    public function createPage(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.create');

        $payload = $this->payload();

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            $this->log($key, 'error', 'page', null, 'Missing title.');
            $this->fail(422, 'title is required.');
        }

        $content = $this->resolveContent($payload);
        if ($content === null) {
            $this->log($key, 'error', 'page', null, 'Missing content.');
            $this->fail(422, 'Provide content_md (markdown) or content (HTML).');
        }

        $status = (string) ($payload['status'] ?? 'draft');
        if ($status === 'published') {
            $this->requireGrant($key, 'pages.publish');
        } elseif ($status !== 'draft') {
            $this->log($key, 'error', 'page', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status must be one of: draft, published.');
        }

        try {
            $page = $this->svc('pages')->createPage([
                'title'          => $title,
                'content'        => $content,
                'status'         => $status,
                'allow_comments' => !empty($payload['allow_comments']) ? 1 : 0,
                'ai_generated'   => 1,
            ], $this->app->ai()->defaultAuthorId());
        } catch (\Throwable $e) {
            $this->log($key, 'error', 'page', null, $e->getMessage());
            $this->fail(500, 'The page could not be created: ' . $e->getMessage());
        }

        $this->saveSeo('page', (int) $page->id, $payload);

        $this->log($key, 'ok', 'page', (int) $page->id, "Created page #{$page->id}.");
        $this->ok([
            'page' => $this->app->ai()->serializePage($page),
            'url'  => $this->app->pluginLoader()->routePrefix('pubvana/pages') . '/' . $page->slug,
            'id'   => (int) $page->id,
        ]);
    }

    public function updatePage(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.update');

        $payload = $this->payload();
        $pages = $this->svc('pages');

        $existing = $pages->findPage((int) $id);
        if ($existing === null) {
            $this->log($key, 'error', 'page', (int) $id, 'Page not found.');
            $this->fail(404, 'Page not found.');
        }

        $status = (string) ($payload['status'] ?? (string) $existing->status);
        if ($status === 'published' && (string) $existing->status !== 'published') {
            $this->requireGrant($key, 'pages.publish');
        } elseif (!in_array($status, ['draft', 'published'], true)) {
            $this->log($key, 'error', 'page', (int) $id, "Invalid status '{$status}'.");
            $this->fail(422, 'status must be one of: draft, published.');
        }

        $content = $this->resolveContent($payload);
        $update = [];
        if (array_key_exists('title', $payload)) {
            $update['title'] = (string) $payload['title'];
        }
        if ($content !== null) {
            $update['content'] = $content;
        }
        $update['status'] = $status;
        if (array_key_exists('allow_comments', $payload)) {
            $update['allow_comments'] = !empty($payload['allow_comments']) ? 1 : 0;
        }

        $page = $pages->updatePage((int) $id, $update);
        $this->saveSeo('page', (int) $id, $payload);
        $this->log($key, 'ok', 'page', (int) $id, "Updated page #{$id}.");
        $this->ok($this->app->ai()->serializePage($page));
    }

    public function deletePage(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'pages.delete');

        $page = $this->svc('pages')->findPage((int) $id);
        if ($page === null) {
            $this->log($key, 'error', 'page', (int) $id, 'Page not found.');
            $this->fail(404, 'Page not found.');
        }

        $this->svc('pages')->deletePage((int) $id);
        $this->log($key, 'ok', 'page', (int) $id, "Deleted page #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    // -----------------------------------------------------------------
    // Comments
    // -----------------------------------------------------------------

    public function comments(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.read');

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));
        $status = $query->status ?? null;
        if ($status !== null && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $this->log($key, 'error', 'comment', null, "Invalid status '{$status}'.");
            $this->fail(422, 'status filter must be one of: pending, approved, rejected.');
        }

        $comments = $this->svc('comments');
        $rows = [];
        foreach ($comments->list($page, $perPage, $status !== null ? (string) $status : null) as $comment) {
            $rows[] = $this->app->ai()->serializeComment($comment);
        }

        $this->log($key, 'ok', 'comment', null, 'Listed comments.');
        $this->ok([
            'items'    => $rows,
            'total'    => $comments->countByStatus($status !== null ? (string) $status : null),
            'page'     => $page,
            'per_page' => $perPage,
            'status'   => $status,
        ]);
    }

    public function approveComment(string $id): void
    {
        $this->moderateComment((int) $id, 'approve');
    }

    public function rejectComment(string $id): void
    {
        $this->moderateComment((int) $id, 'reject');
    }

    public function deleteComment(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.delete');

        if (!$this->svc('comments')->delete((int) $id)) {
            $this->log($key, 'error', 'comment', (int) $id, 'Comment not found.');
            $this->fail(404, 'Comment not found.');
        }

        $this->log($key, 'ok', 'comment', (int) $id, "Deleted comment #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    // -----------------------------------------------------------------
    // Redirects
    // -----------------------------------------------------------------

    public function redirects(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.read');

        $rows = [];
        foreach ($this->svc('redirects')->all() as $redirect) {
            $rows[] = $this->app->ai()->serializeRedirect($redirect);
        }

        $this->log($key, 'ok', 'redirect', null, 'Listed redirects.');
        $this->ok(['total' => count($rows), 'items' => $rows]);
    }

    public function createRedirect(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.create');

        $payload = $this->payload();
        $sourcePath = trim((string) ($payload['source_path'] ?? ''));
        $targetUrl = trim((string) ($payload['target_url'] ?? ''));
        if ($sourcePath === '' || $targetUrl === '') {
            $this->log($key, 'error', 'redirect', null, 'Missing source_path or target_url.');
            $this->fail(422, 'source_path and target_url are required.');
        }

        $redirect = $this->svc('redirects')->create([
            'source_path' => $sourcePath,
            'target_url'  => $targetUrl,
            'status_code' => (int) ($payload['status_code'] ?? 301),
            'enabled'     => !empty($payload['enabled']),
            'notes'       => $this->nullableString($payload['notes'] ?? null),
        ]);

        $this->log($key, 'ok', 'redirect', (int) $redirect->id, "Created redirect #{$redirect->id}.");
        $this->ok($this->app->ai()->serializeRedirect($redirect));
    }

    public function updateRedirect(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.update');

        $redirect = $this->svc('redirects')->update((int) $id, $this->payload());
        if ($redirect === null) {
            $this->log($key, 'error', 'redirect', (int) $id, 'Redirect not found.');
            $this->fail(404, 'Redirect not found.');
        }

        $this->log($key, 'ok', 'redirect', (int) $id, "Updated redirect #{$id}.");
        $this->ok($this->app->ai()->serializeRedirect($redirect));
    }

    public function deleteRedirect(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'redirects.delete');

        if (!$this->svc('redirects')->delete((int) $id)) {
            $this->log($key, 'error', 'redirect', (int) $id, 'Redirect not found.');
            $this->fail(404, 'Redirect not found.');
        }

        $this->log($key, 'ok', 'redirect', (int) $id, "Deleted redirect #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    // -----------------------------------------------------------------
    // Navigation
    // -----------------------------------------------------------------

    public function navigation(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.read');

        $groups = $this->app->ai()->listNavigationByGroup();
        $this->log($key, 'ok', 'navigation', null, 'Listed navigation.');
        $this->ok($groups);
    }

    public function createNavigation(): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.create');

        $payload = $this->payload();
        $label = trim((string) ($payload['label'] ?? ''));
        if ($label === '' || trim((string) ($payload['url'] ?? '')) === '') {
            $this->log($key, 'error', 'navigation', null, 'Missing label or url.');
            $this->fail(422, 'label and url are required.');
        }

        $item = $this->svc('navigation')->create([
            'label'      => $label,
            'url'        => (string) $payload['url'],
            'nav_group'  => (string) ($payload['nav_group'] ?? 'primary'),
            'parent_id'  => !empty($payload['parent_id']) ? (int) $payload['parent_id'] : null,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'target'     => in_array((string) ($payload['target'] ?? '_self'), ['_self', '_blank'], true) ? (string) $payload['target'] : '_self',
        ]);

        $this->log($key, 'ok', 'navigation', (int) $item->id, "Created navigation item #{$item->id}.");
        $this->ok($this->app->ai()->serializeNavigationItem($item));
    }

    public function updateNavigation(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.update');

        $payload = $this->payload();
        if (array_key_exists('label', $payload)) {
            $payload['label'] = trim((string) $payload['label']);
        }
        if (array_key_exists('target', $payload)) {
            $payload['target'] = in_array((string) $payload['target'], ['_self', '_blank'], true) ? (string) $payload['target'] : '_self';
        }

        $item = $this->app->ai()->updateNavigationItem((int) $id, $payload);
        if ($item === null) {
            $this->log($key, 'error', 'navigation', (int) $id, 'Navigation item not found.');
            $this->fail(404, 'Navigation item not found.');
        }

        $this->log($key, 'ok', 'navigation', (int) $id, "Updated navigation item #{$id}.");
        $this->ok($item);
    }

    public function deleteNavigation(string $id): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'navigation.delete');

        if (!$this->svc('navigation')->delete((int) $id)) {
            $this->log($key, 'error', 'navigation', (int) $id, 'Navigation item not found.');
            $this->fail(404, 'Navigation item not found.');
        }

        $this->log($key, 'ok', 'navigation', (int) $id, "Deleted navigation item #{$id}.");
        $this->ok(['deleted' => true, 'id' => (int) $id]);
    }

    // -----------------------------------------------------------------
    // Fact checking
    // -----------------------------------------------------------------

    /**
     * Serve the current fact-checking prompt: versioned terms and
     * instructions. Reachable to any authenticated key, even while the
     * service is off, so an assistant can always read the terms it would
     * be bound by.
     */
    public function factCheckPrompt(): void
    {
        $key = $this->requireKey();

        $factCheck = $this->app->aiFactCheck();
        $prompt = $factCheck->currentPrompt();

        $this->log($key, 'ok', 'fact_check', null, "Fetched fact-checking prompt v{$prompt['version']}.");
        $this->ok([
            'prompt'                => $prompt,
            'fact_checking_enabled' => $factCheck->isEnabled(),
            'terms_current'         => $factCheck->termsCurrent(),
            'note'                  => 'Fetch this prompt before every fact check and follow its terms. Submissions must attest to this prompt version.',
        ]);
    }

    /**
     * List stored fact-check reports, newest first.
     */
    public function factChecks(): void
    {
        $key = $this->requireKey();
        $this->requireFactCheckGate($key);

        $query = $this->app->request()->query;
        $page = max(1, (int) ($query->page ?? 1));
        $perPage = min(100, max(1, (int) ($query->per_page ?? 25)));

        $contentType = $query->content_type ?? null;
        if ($contentType !== null && !in_array((string) $contentType, ['post', 'page'], true)) {
            $this->log($key, 'error', 'fact_check', null, "Invalid content_type '{$contentType}'.");
            $this->fail(422, 'content_type filter must be one of: post, page.');
        }

        $contentId = $query->content_id ?? null;
        if ($contentId !== null && (int) $contentId <= 0) {
            $this->log($key, 'error', 'fact_check', null, "Invalid content_id '{$contentId}'.");
            $this->fail(422, 'content_id filter must be a positive id.');
        }

        $result = $this->app->aiFactCheck()->listReports(
            $page,
            $perPage,
            $contentType !== null ? (string) $contentType : null,
            $contentId !== null ? (int) $contentId : null
        );

        $detail = 'Listed fact-check reports';
        if ($contentType !== null) {
            $detail .= " (content_type: {$contentType})";
        }
        if ($contentId !== null) {
            $detail .= " (content_id: {$contentId})";
        }
        $this->log($key, 'ok', 'fact_check', null, $detail . '.');
        $this->ok($result);
    }

    /**
     * Fetch one stored fact-check report.
     */
    public function factCheck(string $id): void
    {
        $key = $this->requireKey();
        $this->requireFactCheckGate($key);

        $report = $this->app->aiFactCheck()->findReport((int) $id);
        if ($report === null) {
            $this->log($key, 'error', 'fact_check', (int) $id, 'Fact-check report not found.');
            $this->fail(404, 'Fact-check report not found.');
        }

        $this->log($key, 'ok', 'fact_check', (int) $report->id, "Fetched fact-check report #{$report->id}.");
        $this->ok($this->app->aiFactCheck()->serializeReport($report));
    }

    /**
     * Submit a fact-check report for a post. Requires the posts.read
     * grant (the checker must be able to pull the article through the
     * API) and the site-level fact-checking gate.
     */
    public function submitPostFactCheck(string $id): void
    {
        $this->submitFactCheck('post', (int) $id);
    }

    /**
     * Submit a fact-check report for a page.
     */
    public function submitPageFactCheck(string $id): void
    {
        $this->submitFactCheck('page', (int) $id);
    }

    // -----------------------------------------------------------------
    // Stubs
    // -----------------------------------------------------------------

    public function brokenLinks(): void
    {
        $key = $this->requireKey();
        $this->log($key, 'error', null, null, 'Not implemented.');
        $this->fail(501, 'Coming soon.');
    }

    public function analytics(): void
    {
        $key = $this->requireKey();
        $this->log($key, 'error', null, null, 'Not implemented.');
        $this->fail(501, 'Coming soon.');
    }

    // -----------------------------------------------------------------
    // Shared logic
    // -----------------------------------------------------------------

    /**
     * Resolve a post's target status and published_at with grant checks.
     *
    /**
     * @param array<string, mixed> $payload Posted payload
     * @param \Pubvana\Plugins\Blog\Models\Post|null $existing Post being updated, or null when creating
     * @return array{0: string, 1: ?string} [status, published_at]
     */
    protected function resolvePostStatus(AiKey $key, array $payload, $existing): array
    {
        $status = (string) ($payload['status'] ?? 'draft');
        $publishedAt = null;

        if ($status === 'published') {
            $this->requireGrant($key, 'posts.publish');
            $publishedAt = $this->now();
            if ($existing !== null && (string) $existing->status === 'published' && $existing->published_at !== null) {
                $publishedAt = (string) $existing->published_at;
            }
        } elseif ($status === 'scheduled') {
            $this->requireGrant($key, 'posts.schedule');
            $publishOn = (string) ($payload['publish_on'] ?? ($existing !== null ? (string) ($existing->published_at ?? '') : ''));
            if ($publishOn === '' || !$this->isDatetime($publishOn)) {
                $this->log($key, 'error', 'post', $existing !== null ? (int) $existing->id : null, 'Invalid publish_on.');
                $this->fail(422, "status 'scheduled' requires a valid publish_on (e.g. 2026-09-01 09:00:00).");
            }
            $ts = strtotime($publishOn);
            $publishedAt = $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        } elseif ($status !== 'draft') {
            $this->log($key, 'error', 'post', $existing !== null ? (int) $existing->id : null, "Invalid status '{$status}'.");
            $this->fail(422, 'status must be one of: draft, published, scheduled.');
        }

        return [$status, $publishedAt];
    }

    protected function moderateComment(int $id, string $action): void
    {
        $key = $this->requireKey();
        $this->requireGrant($key, 'comments.' . $action);

        $comment = $action === 'approve'
            ? $this->svc('comments')->approve($id)
            : $this->svc('comments')->reject($id);

        if ($comment === null) {
            $this->log($key, 'error', 'comment', $id, 'Comment not found.');
            $this->fail(404, 'Comment not found.');
        }

        $this->log($key, 'ok', 'comment', $id, ucfirst($action) . 'd comment #' . $id . '.');
        $this->ok($this->app->ai()->serializeComment($comment));
    }

    /**
     * Shared submit path for post and page fact-check reports.
     *
     * @param string $contentType 'post' or 'page'
     */
    protected function submitFactCheck(string $contentType, int $contentId): void
    {
        $key = $this->requireKey();

        // The checker must be able to pull the article through the API:
        // a key that cannot read content has no business writing reports
        // about it.
        $this->requireGrant($key, $contentType === 'page' ? 'pages.read' : 'posts.read');

        $this->requireFactCheckGate($key);

        $result = $this->app->aiFactCheck()->submitReport($contentType, $contentId, $this->payload(), $key);
        if (!$result['ok'] || !($result['report'] instanceof \Pubvana\Plugins\AiAssistant\Models\AiFactCheck)) {
            $this->log($key, 'error', 'fact_check', $contentId, $result['error']);
            $this->fail($result['code'], $result['error']);
        }

        $report = $result['report'];
        $this->log(
            $key,
            'ok',
            'fact_check',
            $contentId,
            "Submitted fact check for {$contentType} #{$contentId} "
            . "(overall: {$report->overall_verdict}, claims: {$report->claim_count}, prompt v{$report->prompt_version})"
            . ($report->prompt_interference === 1 ? ' [interference flagged]' : '') . '.'
        );
        $this->ok($this->app->aiFactCheck()->serializeReport($report));
    }

    /**
     * Site-level fact-checking gate: the toggle is the grant.
     *
     * Runs before every fact-check endpoint except the prompt endpoint.
     */
    protected function requireFactCheckGate(AiKey $key): void
    {
        $gate = $this->app->aiFactCheck()->gateStatus();
        if (!$gate['ok']) {
            $this->log($key, 'denied', 'fact_check', null, $gate['message']);
            $this->fail($gate['code'], $gate['message']);
        }
    }

    // -----------------------------------------------------------------
    // Request/response plumbing
    // -----------------------------------------------------------------

    /**
     * Authenticate the request and return the key, or terminate with 401.
     */
    protected function requireKey(): AiKey
    {
        $token = $this->bearerToken();
        if ($token === null) {
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, 'Missing bearer token.');
            $this->fail(401, 'Missing Authentication header. Send the key as "Authorization: Bearer <key>".');
        }

        $auth = $this->app->ai()->authenticate($token);
        if ($auth['key'] === null) {
            $this->app->ai()->log(
                $this->method(),
                $this->path(),
                'error',
                $auth['key_known'] ?? null,
                null,
                null,
                $auth['error'] ?? 'Authentication failed.'
            );
            $this->fail(401, $auth['error'] ?? 'Authentication failed.');
        }

        return $auth['key'];
    }

    /**
     * Require a specific grant or terminate with a 403 hard failure.
     */
    protected function requireGrant(AiKey $key, string $permission): void
    {
        if (!$this->app->ai()->hasGrant($key, $permission)) {
            $this->app->ai()->log($this->method(), $this->path(), 'denied', $key, null, null, "Missing grant: {$permission}");
            $this->fail(403, "This API key does not have the '{$permission}' grant.");
        }
    }

    protected function log(AiKey $key, string $outcome, ?string $entityType, ?int $entityId, ?string $detail): void
    {
        $this->app->ai()->log($this->method(), $this->path(), $outcome, $key, $entityType, $entityId, $detail);
    }

    /**
     * @return mixed Whatever the named facade returns, or never (503 halt)
     */
    protected function svc(string $name)
    {
        try {
            return $this->app->{$name}();
        } catch (\Throwable $e) {
            $this->app->ai()->log($this->method(), $this->path(), 'error', null, null, null, "Unavailable service: {$name}");
            $this->fail(503, "The '{$name}' feature is not available right now.");
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    protected function ok(array $data): void
    {
        $this->app->jsonHalt([
            'status' => 'ok',
            'data'   => $data,
            'errors' => [],
        ], 200);
    }

    protected function fail(int $status, string $message): never
    {
        $this->app->jsonHalt([
            'status' => 'error',
            'data'   => null,
            'errors' => [['code' => $status, 'message' => $message]],
        ], $status);
    }

    protected function bearerToken(): ?string
    {
        $header = $this->app->request()->getHeader('Authorization');

        // Apache/mod_php does not always expose Authorization in
        // $_SERVER['HTTP_AUTHORIZATION']; getallheaders() sees it reliably.
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
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    protected function payload(): array
    {
        try {
            return $this->app->request()->data->getData();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param object $query Request query collection (flight\util\Collection)
     */
    protected function searchParam($query): ?string
    {
        $search = $query->search ?? null;
        if ($search !== null) {
            $search = trim((string) $search);
            if ($search === '') {
                $search = null;
            }
        }
        return $search;
    }

    /**
     * @param array<string, mixed> $payload Posted payload
     */
    protected function resolveContent(array $payload): ?string
    {
        $md = (string) ($payload['content_md'] ?? '');
        $html = (string) ($payload['content'] ?? '');

        if ($md !== '') {
            return $this->app->aiMarkdown()->toHtml($md);
        }
        if ($html !== '') {
            return $html;
        }
        if (array_key_exists('content_md', $payload) || array_key_exists('content', $payload)) {
            return '';
        }
        return null;
    }

    /**
     * Persist a nested "seo" block for a content item, when present.
     *
     * Soft no-op when the payload carries no SEO block or the SEO plugin is
     * disabled (failing the whole create/update over optional SEO would be
     * wrong). Only explicitly-provided fields are written; others are left
     * untouched, matching SeoService::saveMeta()'s partial-update semantics.
     */
    /**
     * @param array<string, mixed> $payload Posted payload
     */
    protected function saveSeo(string $contentType, int $contentId, array $payload): void
    {
        $block = $payload['seo'] ?? null;
        if (!is_array($block) || $block === []) {
            return;
        }

        try {
            $seo = $this->app->seo();
        } catch (\Throwable) {
            return;
        }

        $fields = [];
        foreach ([
            'meta_title', 'meta_description', 'canonical_url', 'robots_directive',
            'og_title', 'og_description', 'og_image', 'og_type', 'twitter_card', 'hreflang',
        ] as $field) {
            if (array_key_exists($field, $block)) {
                $fields[$field] = $this->nullableString($block[$field]);
            }
        }

        if (array_key_exists('focus_keywords', $block)) {
            $keywords = $block['focus_keywords'];
            if (is_array($keywords)) {
                $keywords = array_filter(array_map('strval', $keywords));
            } else {
                $keywords = array_filter(array_map('trim', explode(',', (string) $keywords)));
            }
            $fields['focus_keywords'] = array_values($keywords);
        }

        if ($fields === []) {
            return;
        }

        $seo->saveMeta($contentType, $contentId, $fields);
    }

    /**
     * @return array<int, int>
     */
    protected function categoryIds(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    protected function isDatetime(string $value): bool
    {
        return strtotime($value) !== false;
    }

    protected function now(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }
}