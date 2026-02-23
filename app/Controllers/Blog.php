<?php

namespace App\Controllers;

use App\Models\AuthorProfileModel;
use App\Models\CategoryModel;
use App\Models\CommentModel;
use App\Models\PostModel;
use App\Models\TagModel;
use App\Services\SeoService;
use App\Services\ThemeService;

class Blog extends BaseController
{
    protected PostModel    $postModel;
    protected SeoService   $seoService;
    protected ThemeService $themeService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->postModel  = new PostModel();
        $this->seoService = new SeoService();
    }

    public function index(): string
    {
        // Configurable front page
        $frontPageType = setting('App.frontPageType') ?? 'blog';
        if ($frontPageType === 'page') {
            $pageId = setting('App.frontPageId');
            if ($pageId) {
                $page = (new \App\Models\PageModel())->find($pageId);
                if ($page && $page->status === 'published') {
                    return $this->themeService->view('page', array_merge($this->data, [
                        'page' => $page,
                        'seo'  => $this->seoService->getMeta($page),
                    ]));
                }
            }
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->orderBy('published_at', 'DESC')
            ->paginate($perPage, 'default');

        return $this->themeService->view('home', array_merge($this->data, [
            'posts'  => $posts,
            'pager'  => $this->postModel->pager,
            'seo'    => $this->seoService->getDefaultMeta(),
        ]));
    }

    public function post(string $slug): string
    {
        $post = $this->postModel->published()->findBySlug($slug);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->postModel->incrementViews($post->id);

        $commentModel = new CommentModel();
        $comments     = $commentModel->getTree((int) $post->id);

        // Handle comment submission
        $commentSaved = false;
        if ($this->request->getMethod() === 'post' && setting('App.commentsEnabled')) {
            $commentSaved = $this->handleComment($post);
            if ($commentSaved) {
                return redirect()->to(post_url($slug) . '#comments')->with('success', 'Your comment is awaiting moderation.');
            }
        }

        $authorProfile = null;
        if ($post->author_id) {
            $profileModel  = new AuthorProfileModel();
            $profile       = $profileModel->getByUserId((int) $post->author_id);
            if ($profile) {
                // Attach username/email from users table for gravatar fallback
                $userRow = db_connect()->table('users u')
                    ->select('u.username, ai.secret AS email')
                    ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
                    ->where('u.id', $post->author_id)
                    ->get()->getRowObject();
                if ($userRow) {
                    $profile->username = $userRow->username;
                    $profile->email    = $userRow->email;
                }
                $authorProfile = $profile;
            }
        }

        return $this->themeService->view('post', array_merge($this->data, [
            'post'           => $post,
            'comments'       => $comments,
            'author_profile' => $authorProfile,
            'seo'            => $this->seoService->getMeta($post),
        ]));
    }

    protected function handleComment(object $post): bool
    {
        if (! $this->validate([
            'author_name'  => 'required|max_length[100]',
            'author_email' => 'required|valid_email|max_length[255]',
            'content'      => 'required|min_length[3]|max_length[2000]',
        ])) {
            return false;
        }

        $status = setting('App.commentModeration') ? 'pending' : 'approved';
        $model  = new CommentModel();
        $model->insert([
            'post_id'      => $post->id,
            'author_name'  => $this->request->getPost('author_name'),
            'author_email' => $this->request->getPost('author_email'),
            'content'      => $this->request->getPost('content'),
            'parent_id'    => $this->request->getPost('parent_id') ?: null,
            'user_id'      => auth()->loggedIn() ? auth()->id() : null,
            'status'       => $status,
        ]);

        return true;
    }

    public function category(string $slug): string
    {
        $catModel = new CategoryModel();
        $category = $catModel->findBySlug($slug);
        if (! $category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->byCategory($category->id)
            ->orderBy('posts.published_at', 'DESC')
            ->paginate($perPage, 'default');

        return $this->themeService->view('category', array_merge($this->data, [
            'category' => $category,
            'posts'    => $posts,
            'pager'    => $this->postModel->pager,
            'seo'      => $this->seoService->getMeta($category),
        ]));
    }

    public function tag(string $slug): string
    {
        $tagModel = new TagModel();
        $tag      = $tagModel->findBySlug($slug);
        if (! $tag) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->byTag($tag->id)
            ->orderBy('posts.published_at', 'DESC')
            ->paginate($perPage, 'default');

        return $this->themeService->view('tag', array_merge($this->data, [
            'tag'   => $tag,
            'posts' => $posts,
            'pager' => $this->postModel->pager,
            'seo'   => $this->seoService->getMeta($tag),
        ]));
    }

    public function archive(int $year, int $month): string
    {
        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = $this->postModel->published()
            ->where("YEAR(published_at)", $year)
            ->where("MONTH(published_at)", $month)
            ->orderBy('published_at', 'DESC')
            ->paginate($perPage);

        $fake = (object) ['title' => date('F Y', mktime(0, 0, 0, $month, 1, $year))];
        return $this->themeService->view('archive', array_merge($this->data, [
            'posts'   => $posts,
            'pager'   => $this->postModel->pager,
            'year'    => $year,
            'month'   => $month,
            'archive' => $fake,
            'seo'     => $this->seoService->getMeta($fake),
        ]));
    }
}
