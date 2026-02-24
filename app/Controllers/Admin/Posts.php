<?php

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Models\TagModel;
use App\Services\SocialSharingService;

class Posts extends BaseAdminController
{
    protected PostModel $postModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->postModel = new PostModel();
    }

    public function index(): string
    {
        $filter = $this->request->getGet('status') ?? '';
        if (! in_array($filter, ['', 'draft', 'published', 'scheduled'], true)) {
            $filter = '';
        }
        $posts  = $this->postModel->withDeleted();
        if ($filter) {
            $posts = $posts->where('posts.status', $filter);
        }
        $posts = $posts->select('posts.id, posts.title, posts.slug, posts.status, posts.published_at, posts.created_at, posts.author_id, posts.views')
            ->orderBy('posts.created_at', 'DESC')
            ->paginate(20);

        return $this->adminView('posts/index', array_merge($this->baseData('Posts', 'posts'), [
            'posts'  => $posts,
            'pager'  => $this->postModel->pager,
            'filter' => $filter,
        ]));
    }

    public function create(): string
    {
        if (! auth()->user()->can('posts.create')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('posts/create', array_merge($this->baseData('New Post', 'posts'), [
            'categories' => (new CategoryModel())->findAll(),
            'tags'       => (new TagModel())->findAll(),
        ]));
    }

    public function store()
    {
        if (! auth()->user()->can('posts.create')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        if (! $this->validate([
            'title'   => 'required|max_length[255]',
            'content' => 'permit_empty',
            'status'  => 'required|in_list[draft,published,scheduled]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $slug = slug_from_title($this->request->getPost('title'));
        $existing = $this->postModel->where('slug', $slug)->first();
        if ($existing) {
            $slug .= '-' . time();
        }

        $publishedAt = null;
        if ($this->request->getPost('status') === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        } elseif ($this->request->getPost('published_at')) {
            $publishedAt = $this->request->getPost('published_at');
        }

        $status          = $this->request->getPost('status');
        $shareOnPublish  = $this->request->getPost('share_on_publish') ? 1 : 0;

        $id = $this->postModel->insert([
            'title'            => $this->request->getPost('title'),
            'slug'             => $slug,
            'content'          => $this->request->getPost('content'),
            'content_type'     => $this->request->getPost('content_type') ?? 'html',
            'excerpt'          => $this->request->getPost('excerpt'),
            'status'           => $status,
            'featured_image'   => $this->request->getPost('featured_image'),
            'author_id'        => auth()->id(),
            'published_at'     => $publishedAt,
            'is_featured'      => $this->request->getPost('is_featured') ? 1 : 0,
            'share_on_publish' => $shareOnPublish,
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
        ]);

        $this->syncCategories($id, $this->request->getPost('categories') ?? []);
        $this->syncTags($id, $this->request->getPost('tags_raw') ?? '');
        $this->postModel->generateToken($id);

        if ($status === 'published') {
            $this->saveRevision($id);
        }

        if ($status === 'published' && $shareOnPublish) {
            $post = $this->postModel->find($id);
            (new SocialSharingService())->share($post);
        }

        return redirect()->to('/admin/posts')->with('success', 'Post created successfully.');
    }

    public function edit(int $id): string
    {
        $post = $this->postModel->find($id);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $selectedCats = db_connect()->table('posts_to_categories')->where('post_id', $id)->get()->getResultObject();
        $catIds       = array_column((array) $selectedCats, 'category_id');

        $postTags = db_connect()->table('tags_to_posts ttp')
            ->select('t.name')
            ->join('tags t', 't.id = ttp.tag_id')
            ->where('ttp.post_id', $id)
            ->get()->getResultObject();
        $tagNames = implode(', ', array_column((array) $postTags, 'name'));

        $revisionCount = db_connect()->table('post_revisions')->where('post_id', $id)->countAllResults();

        if (empty($post->preview_token)) {
            $this->postModel->generateToken($id);
            $post = $this->postModel->find($id);
        }
        $previewUrl = base_url('preview/' . $post->preview_token);

        return $this->adminView('posts/edit', array_merge($this->baseData('Edit Post', 'posts'), [
            'post'           => $post,
            'categories'     => (new CategoryModel())->findAll(),
            'selected_cats'  => $catIds,
            'tags_raw'       => $tagNames,
            'revision_count' => $revisionCount,
            'preview_url'    => $previewUrl,
        ]));
    }

    public function update(int $id)
    {
        $post = $this->postModel->find($id);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! auth()->user()->can('posts.edit.any') && $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', 'You can only edit your own posts.');
        }

        if (! $this->validate([
            'title'  => 'required|max_length[255]',
            'status' => 'required|in_list[draft,published,scheduled]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Snapshot current state before overwriting
        $this->saveRevision($id);

        $newStatus      = $this->request->getPost('status');
        $shareOnPublish = $this->request->getPost('share_on_publish') ? 1 : 0;
        $wasPublished   = $post->status === 'published';

        $publishedAt = $post->published_at;
        if ($newStatus === 'published' && ! $publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $this->postModel->update($id, [
            'title'            => $this->request->getPost('title'),
            'content'          => $this->request->getPost('content'),
            'content_type'     => $this->request->getPost('content_type') ?? 'html',
            'excerpt'          => $this->request->getPost('excerpt'),
            'status'           => $newStatus,
            'featured_image'   => $this->request->getPost('featured_image'),
            'published_at'     => $publishedAt,
            'is_featured'      => $this->request->getPost('is_featured') ? 1 : 0,
            'share_on_publish' => $shareOnPublish,
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
        ]);

        $this->syncCategories($id, $this->request->getPost('categories') ?? []);
        $this->syncTags($id, $this->request->getPost('tags_raw') ?? '');

        // Trigger sharing only when transitioning to published for the first time
        if ($newStatus === 'published' && ! $wasPublished && $shareOnPublish) {
            $updated = $this->postModel->find($id);
            (new SocialSharingService())->share($updated);
        }

        return redirect()->to('/admin/posts')->with('success', 'Post updated.');
    }

    public function bulk()
    {
        $action = $this->request->getPost('action');
        $ids    = $this->request->getPost('ids') ?? [];

        if (empty($ids) || ! in_array($action, ['publish', 'unpublish', 'delete'], true)) {
            return redirect()->back()->with('error', 'Invalid bulk action.');
        }

        foreach ($ids as $id) {
            match ($action) {
                'publish'   => $this->postModel->update($id, ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]),
                'unpublish' => $this->postModel->update($id, ['status' => 'draft']),
                'delete'    => $this->postModel->delete($id),
            };
        }

        return redirect()->to('/admin/posts')->with('success', count($ids) . ' post(s) updated.');
    }

    public function delete(int $id)
    {
        $post = $this->postModel->find($id);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (! auth()->user()->can('posts.delete') && $post->author_id !== auth()->id()) {
            return redirect()->to('/admin/posts')->with('error', 'Permission denied.');
        }
        $this->postModel->delete($id);
        return redirect()->to('/admin/posts')->with('success', 'Post deleted.');
    }

    private function saveRevision(int $postId): void
    {
        $post = $this->postModel->find($postId);
        if (! $post) {
            return;
        }
        $db = db_connect();
        $db->table('post_revisions')->insert([
            'post_id'          => $postId,
            'author_id'        => auth()->id(),
            'title'            => $post->title,
            'content'          => $post->content,
            'content_type'     => $post->content_type ?? 'html',
            'excerpt'          => $post->excerpt,
            'status'           => $post->status,
            'meta_title'       => $post->meta_title,
            'meta_description' => $post->meta_description,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // Prune: keep only the 20 most recent revisions per post
        $ids = $db->table('post_revisions')
                  ->select('id')
                  ->where('post_id', $postId)
                  ->orderBy('id', 'DESC')
                  ->limit(20)
                  ->get()->getResultArray();
        if (count($ids) >= 20) {
            $keepIds = array_column($ids, 'id');
            $db->table('post_revisions')
               ->where('post_id', $postId)
               ->whereNotIn('id', $keepIds)
               ->delete();
        }
    }

    protected function syncCategories(int $postId, array $catIds): void
    {
        $db = db_connect();
        $db->table('posts_to_categories')->where('post_id', $postId)->delete();
        foreach ($catIds as $catId) {
            $db->table('posts_to_categories')->insert(['post_id' => $postId, 'category_id' => (int) $catId]);
        }
    }

    protected function syncTags(int $postId, string $tagsRaw): void
    {
        $db       = db_connect();
        $tagModel = new TagModel();
        $db->table('tags_to_posts')->where('post_id', $postId)->delete();
        $names = array_filter(array_map('trim', explode(',', $tagsRaw)));
        foreach ($names as $name) {
            $slug = slug_from_title($name);
            $tag  = $tagModel->where('slug', $slug)->first();
            if (! $tag) {
                $tagId = $tagModel->insert(['name' => $name, 'slug' => $slug], true);
            } else {
                $tagId = $tag->id;
            }
            $db->table('tags_to_posts')->ignore(true)->insert(['post_id' => $postId, 'tag_id' => $tagId]);
        }
    }
}
