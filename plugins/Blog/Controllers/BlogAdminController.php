<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Controllers;

use Pubvana\Controllers\Admin\AdminController;

/**
 * BlogAdminController - Admin CRUD for posts, categories, and tags.
 *
 * @package Pubvana\Plugins\Blog\Controllers
 */
class BlogAdminController extends AdminController
{
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/blog'), '/');
    }

    // ─── Posts ─────────────────────────────────────────────────────────────

    public function index(): void
    {
        $request = $this->app->request();
        $page    = max(1, (int) ($request->query->page ?? 1));
        $status  = $request->query->status ?? null;

        if ($status !== null && !in_array($status, ['draft', 'published', 'scheduled'], true)) {
            $status = null;
        }

        $result = $this->app->blog()->listPosts($page, 25, $status);

        $this->render('pubvana/blog/admin/index', [
            'pageTitle' => 'Posts',
            'adminBase' => $this->adminBase(),
            'posts'     => $result['items'],
            'total'     => $result['total'],
            'page'      => $result['page'],
            'perPage'   => $result['per_page'],
            'status'    => $status,
        ]);
    }

    public function create(): void
    {
        $categories = $this->app->blog()->listCategories();

        $joditHtml = $this->app->media()->joditInit('#content');

        $this->render('pubvana/blog/admin/create', [
            'pageTitle'  => 'New Post',
            'adminBase'  => $this->adminBase(),
            'categories' => $categories,
            'joditHtml'  => $joditHtml,
        ]);
    }

    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $slug = $this->app->slugify($post['slug'] ?? '' ?: $post['title'] ?? '');

        if ($this->app->blog()->postSlugExists($slug)) {
            $slug .= '-' . time();
        }

        $user = $this->app->auth()->user();

        $publishedAt = null;
        $status = $post['status'] ?? 'draft';
        if ($status === 'published') {
            $publishedAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        } elseif ($status === 'scheduled' && !empty($post['published_at'])) {
            $publishedAt = $post['published_at'];
        }

        $newPost = $this->app->blog()->createPost([
            'title'            => $post['title'] ?? '',
            'slug'             => $slug,
            'content'          => $post['content'] ?? '',
            'excerpt'          => $post['excerpt'] ?? null,
            'status'           => $status,
            'featured_image'   => $post['featured_image'] ?? null,
            'media_id'         => !empty($post['media_id']) ? (int) $post['media_id'] : null,
            'published_at'     => $publishedAt,
            'is_featured'      => !empty($post['is_featured']) ? 1 : 0,
            'allow_comments'   => !empty($post['allow_comments']) ? 1 : 0,
            'purify_content'   => !empty($post['purify_content']),
        ], (int) ($user?->id));

        $this->app->blog()->syncPostCategories((int) $newPost->id, $post['categories'] ?? []);
        $this->app->blog()->syncPostTags((int) $newPost->id, $post['tags_raw'] ?? '');

        $this->app->session()->flash('success', 'Post created.');
        $this->app->redirect($this->adminBase() . '/' . $newPost->id . '/edit');
    }

    public function edit(string $id): void
    {
        $post = $this->app->blog()->findPost((int) $id);

        if ($post === null) {
            $this->app->redirect($this->adminBase());
            return;
        }

        $categories   = $this->app->blog()->listCategories();
        $selectedCats = $this->app->blog()->getPostCategoryIds((int) $id);
        $tagsRaw      = implode(', ', $this->app->blog()->getPostTagNames((int) $id));

        $joditHtml = $this->app->media()->joditInit('#content');

        $this->render('pubvana/blog/admin/edit', [
            'pageTitle'    => 'Edit Post',
            'adminBase'    => $this->adminBase(),
            'previewBase'  => $this->app->pluginLoader()->routePrefix('pubvana/blog'),
            'post'         => $post,
            'categories'   => $categories,
            'selectedCats' => $selectedCats,
            'tagsRaw'      => $tagsRaw,
            'joditHtml'    => $joditHtml,
        ]);
    }

    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $user = $this->app->auth()->user();
        $status = $post['status'] ?? 'draft';

        $existing = $this->app->blog()->findPost((int) $id);
        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = ($existing && $existing->status === 'published')
                ? $existing->published_at
                : (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        } elseif ($status === 'scheduled' && !empty($post['published_at'])) {
            $publishedAt = $post['published_at'];
        }

        $this->app->blog()->updatePost((int) $id, [
            'title'            => $post['title'] ?? '',
            'content'          => $post['content'] ?? '',
            'excerpt'          => $post['excerpt'] ?? null,
            'status'           => $status,
            'featured_image'   => $post['featured_image'] ?? null,
            'media_id'         => !empty($post['media_id']) ? (int) $post['media_id'] : null,
            'published_at'     => $publishedAt,
            'is_featured'      => !empty($post['is_featured']) ? 1 : 0,
            'allow_comments'   => !empty($post['allow_comments']) ? 1 : 0,
            'purify_content'   => !empty($post['purify_content']),
        ], (int) ($user?->id));

        $this->app->blog()->syncPostCategories((int) $id, $post['categories'] ?? []);
        $this->app->blog()->syncPostTags((int) $id, $post['tags_raw'] ?? '');

        $this->app->session()->flash('success', 'Post updated.');
        $this->app->redirect($this->adminBase() . '/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->app->blog()->deletePost((int) $id);
        $this->app->session()->flash('success', 'Post deleted.');
        $this->app->redirect($this->adminBase());
    }

    public function revisions(string $id): void
    {
        $post = $this->app->blog()->findPost((int) $id);

        if ($post === null) {
            $this->app->redirect($this->adminBase());
            return;
        }

        $revisions = $this->app->blog()->getRevisions((int) $id);

        $this->render('pubvana/blog/admin/revisions', [
            'pageTitle' => 'Revisions: ' . $post->title,
            'adminBase' => $this->adminBase(),
            'post'      => $post,
            'revisions' => $revisions,
        ]);
    }

    public function restore(string $id, string $revisionId): void
    {
        $user = $this->app->auth()->user();
        $this->app->blog()->restoreRevision((int) $id, (int) $revisionId, (int) ($user?->id));
        $this->app->session()->flash('success', 'Revision restored.');
        $this->app->redirect($this->adminBase() . '/' . $id . '/edit');
    }

    // ─── Categories ───────────────────────────────────────────────────────

    public function categories(): void
    {
        $categories = $this->app->blog()->listCategories();

        $this->render('pubvana/blog/admin/categories', [
            'pageTitle'  => 'Categories',
            'adminBase'  => $this->adminBase(),
            'categories' => $categories,
        ]);
    }

    public function createCategory(): void
    {
        $categories = $this->app->blog()->listCategories();

        $this->render('pubvana/blog/admin/category-form', [
            'pageTitle'  => 'New Category',
            'adminBase'  => $this->adminBase(),
            'category'   => null,
            'categories' => $categories,
        ]);
    }

    public function storeCategory(): void
    {
        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $slug = $this->app->slugify($data['slug'] ?? '' ?: $data['name'] ?? '');

        if ($this->app->blog()->categorySlugExists($slug)) {
            $this->app->session()->flash('error', 'A category with that slug already exists.');
            $this->app->redirect($this->adminBase() . '/categories/create');
            return;
        }

        $this->app->blog()->createCategory([
            'name'        => $data['name'] ?? '',
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'parent_id'   => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
        ]);

        $this->app->session()->flash('success', 'Category created.');
        $this->app->redirect($this->adminBase() . '/categories');
    }

    public function editCategory(string $id): void
    {
        $category = $this->app->blog()->findCategory((int) $id);

        if ($category === null) {
            $this->app->redirect($this->adminBase() . '/categories');
            return;
        }

        $categories = $this->app->blog()->listCategories();

        $this->render('pubvana/blog/admin/category-form', [
            'pageTitle'  => 'Edit Category',
            'adminBase'  => $this->adminBase(),
            'category'   => $category,
            'categories' => $categories,
        ]);
    }

    public function updateCategory(string $id): void
    {
        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $slug = $this->app->slugify($data['slug'] ?? '' ?: $data['name'] ?? '');

        if ($this->app->blog()->categorySlugExists($slug, (int) $id)) {
            $this->app->session()->flash('error', 'A category with that slug already exists.');
            $this->app->redirect($this->adminBase() . '/categories/' . $id . '/edit');
            return;
        }

        $this->app->blog()->updateCategory((int) $id, [
            'name'        => $data['name'] ?? '',
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'parent_id'   => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
        ]);

        $this->app->session()->flash('success', 'Category updated.');
        $this->app->redirect($this->adminBase() . '/categories');
    }

    public function deleteCategory(string $id): void
    {
        $this->app->blog()->deleteCategory((int) $id);
        $this->app->session()->flash('success', 'Category deleted.');
        $this->app->redirect($this->adminBase() . '/categories');
    }

    // ─── Tags ─────────────────────────────────────────────────────────────

    public function tags(): void
    {
        $tags = $this->app->blog()->listTags();

        $this->render('pubvana/blog/admin/tags', [
            'pageTitle' => 'Tags',
            'adminBase' => $this->adminBase(),
            'tags'      => $tags,
        ]);
    }

    public function deleteTag(string $id): void
    {
        $this->app->blog()->deleteTag((int) $id);
        $this->app->session()->flash('success', 'Tag deleted.');
        $this->app->redirect($this->adminBase() . '/tags');
    }
}
