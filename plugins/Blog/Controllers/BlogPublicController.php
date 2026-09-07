<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Blog\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Pubvana\Plugins\Blog\Models\Post;

/**
 * BlogPublicController - Public-facing blog routes.
 *
 * Listing, single post, category, tag, and preview.
 *
 * @package Pubvana\Plugins\Blog\Controllers
 */
class BlogPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.blog');
    }

    /**
     * Paginated blog listing.
     *
     * The current page arrives either as a clean route segment
     * (/blog/page/@page) or, for backward compatibility, the
     * ?page= query string.
     *
     * @param string|null $page Route page number segment, if any
     */
    public function index(?string $page = null): void
    {
        $pageNum = max(1, (int) ($page ?? $this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($pageNum, 10, 'published');

        $maps = $this->taxonomyMapsFor($result['items']);
        $posts = array_map(fn($post) => $this->formatPost($post, $maps['categories'], $maps['tags'], $maps['authors']), $result['items']);

        $this->render('home', [
            'posts'      => $posts,
            'pagination' => $this->buildPagination($result, $this->app->pluginLoader()->routePrefix('pubvana/blog')),
        ]);
    }

    /**
     * List all categories.
     */
    public function categories(): void
    {
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $categories = $this->app->blog()->listCategories();
        $list = [];

        foreach ($categories as $cat) {
            $list[] = [
                'name'       => $cat->name,
                'slug'       => $cat->slug,
                'url'        => $prefix . '/category/' . $cat->slug,
                'post_count' => $cat->post_count ?? null,
            ];
        }

        $this->render('categories', [
            'categories' => $list,
        ]);
    }

    /**
     * List all tags.
     */
    public function tags(): void
    {
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $tags = $this->app->blog()->listTags();
        $list = [];

        foreach ($tags as $tag) {
            $list[] = [
                'name' => $tag->name,
                'slug' => $tag->slug,
                'url'  => $prefix . '/tag/' . $tag->slug,
            ];
        }

        $this->render('tags', [
            'tags' => $list,
        ]);
    }

    /**
     * Single published post by slug.
     */
    public function show(string $slug): void
    {
        $post = $this->app->blog()->findPostBySlug($slug);

        if ($post === null || $post->status !== 'published') {
            $this->app->halt(404, 'Post not found');
            return;
        }

        $this->app->blog()->recordView((int) $post->id);

        $categories = $this->getPostCategories((int) $post->id);
        $tags = $this->getPostTags((int) $post->id);

        $data = [
            'title'          => $post->title,
            'content'        => $post->content,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at,
            'author'         => $this->getAuthor($post),
            'ai_disclosure'  => $this->aiDisclosure((int) ($post->ai_generated ?? 0)),
            'categories'     => $categories,
            'tags'           => $tags,
            'commentable'    => ['type' => 'blog', 'id' => (int) $post->id],
            'allow_comments' => (bool) $post->allow_comments,
            'seo_context'    => [
                'content_type' => 'post',
                'content_id'   => (int) $post->id,
                'title'        => $post->title,
                'description'  => $post->excerpt ?? '',
                'image'        => (string) ($post->featured_image ?? ''),
                'ai_generated' => !empty($post->ai_generated),
                'published_at' => $post->published_at,
                'updated_at'   => $post->updated_at,
                'og_type'      => 'article',
            ],
        ];

        $this->render('post', $data);
    }

    /**
     * Posts filtered by category slug.
     */
    public function category(string $slug): void
    {
        $categories = $this->app->blog()->listCategories();
        $category = null;
        foreach ($categories as $cat) {
            if ($cat->slug === $slug) {
                $category = $cat;
                break;
            }
        }

        if ($category === null) {
            $this->app->halt(404, 'Category not found');
            return;
        }

        $page = max(1, (int) ($this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($page, 10, 'published');

        $maps = $this->taxonomyMapsFor($result['items']);
        $filtered = [];

        foreach ($result['items'] as $post) {
            $catItems = $maps['categories'][(int) $post->id] ?? [];
            if (in_array((int) $category->id, array_column($catItems, 'id'), true)) {
                $filtered[] = $this->formatPost($post, $maps['categories'], $maps['tags'], $maps['authors']);
            }
        }

        $this->render('archive', [
            'archive_title' => 'Category: ' . $category->name,
            'posts'         => $filtered,
            'pagination'    => null,
        ]);
    }

    /**
     * Posts filtered by tag slug.
     */
    public function tag(string $slug): void
    {
        $tags = $this->app->blog()->listTags();
        $tag = null;
        foreach ($tags as $item) {
            if ($item->slug === $slug) {
                $tag = $item;
                break;
            }
        }

        if ($tag === null) {
            $this->app->halt(404, 'Tag not found');
            return;
        }

        $page = max(1, (int) ($this->app->request()->query->page ?? 1));
        $result = $this->app->blog()->listPosts($page, 10, 'published');

        $maps = $this->taxonomyMapsFor($result['items']);
        $filtered = [];

        foreach ($result['items'] as $post) {
            $tagItems = $maps['tags'][(int) $post->id] ?? [];
            if (in_array($tag->name, array_column($tagItems, 'name'), true)) {
                $filtered[] = $this->formatPost($post, $maps['categories'], $maps['tags'], $maps['authors']);
            }
        }

        $this->render('archive', [
            'archive_title' => 'Tag: ' . $tag->name,
            'posts'         => $filtered,
            'pagination'    => null,
        ]);
    }

    /**
     * Preview a post by its unique preview token.
     */
    public function preview(string $token): void
    {
        $post = $this->app->blog()->findPostByPreviewToken($token);

        if ($post === null) {
            $this->app->halt(404, 'Preview not found');
            return;
        }

        $categories = $this->getPostCategories((int) $post->id);
        $tags = $this->getPostTags((int) $post->id);

        $this->render('post', [
            'title'          => $post->title . ' (Preview)',
            'content'        => $post->content,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at ?? $post->created_at,
            'author'         => $this->getAuthor($post),
            'ai_disclosure'  => $this->aiDisclosure((int) ($post->ai_generated ?? 0)),
            'categories'     => $categories,
            'tags'           => $tags,
            'commentable'    => ['type' => 'blog', 'id' => (int) $post->id],
            'allow_comments' => false,
        ]);
    }

    /**
     * Format a post record into a template-ready array.
     *
     * Listing and archive pages pass prebuilt maps (one batched set of
     * queries for the whole page); single-content paths omit them and
     * fall back to per-post lookups.
     *
     * @param Post                       $post
     * @param array<int, mixed>|null     $categoryMap post id => category items
     * @param array<int, mixed>|null     $tagMap      post id => tag items
     * @param array<int, mixed>|null     $authorMap   author id => author card
     * @return array<string, mixed>
     */
    private function formatPost(object $post, ?array $categoryMap = null, ?array $tagMap = null, ?array $authorMap = null): array
    {
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        return [
            'id'             => (int) $post->id,
            'title'          => $post->title,
            'slug'           => $post->slug,
            'url'            => $prefix . '/' . $post->slug,
            'excerpt'        => $post->excerpt,
            'featured_image' => $this->publicAssetUrl($post->featured_image),
            'published_at'   => $post->published_at,
            'author'         => $authorMap !== null
                ? ($authorMap[(int) ($post->author_id ?? 0)] ?? null)
                : $this->getAuthor($post),
            'categories'     => $categoryMap !== null
                ? ($categoryMap[(int) $post->id] ?? [])
                : $this->getPostCategories((int) $post->id, $prefix),
            'tags'           => $tagMap !== null
                ? ($tagMap[(int) $post->id] ?? [])
                : $this->getPostTags((int) $post->id, $prefix),
        ];
    }

    /**
     * Prebuild category, tag, and author maps for a page of posts.
     *
     * @param array<int, Post> $posts
     * @return array{categories: array<int, list<array<string, mixed>>>, tags: array<int, list<array<string, mixed>>>, authors: array<int, array<string, mixed>|null>}
     */
    private function taxonomyMapsFor(array $posts): array
    {
        $postIds = array_map(fn($post) => (int) $post->id, $posts);
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        $authorIds = [];
        foreach ($posts as $post) {
            if (!empty($post->author_id)) {
                $authorIds[] = (int) $post->author_id;
            }
        }

        return [
            'categories' => $this->app->blog()->categoryItemsForPostIds($postIds, $prefix),
            'tags'       => $this->app->blog()->tagItemsForPostIds($postIds, $prefix),
            'authors'    => $this->app->blog()->authorItemsForIds($authorIds),
        ];
    }

    /**
     * Look up the author for a post.
     *
     * @param Post $post
     * @return array<string, mixed>|null
     */
    private function getAuthor(object $post): ?array
    {
        if (empty($post->author_id)) {
            return null;
        }

        $userId = (int) $post->author_id;

        $user = (new \Enlivenapp\FlightShield\Models\User(\Flight::db()))->findById($userId);
        if ($user === null) {
            return null;
        }

        $username = (string) $user->username;
        $displayName = '';
        try {
            $profile = $this->app->profiles()->findByUserId($userId);
            if ($profile && !empty($profile->display_name)) {
                $displayName = (string) $profile->display_name;
            }
        } catch (\Throwable) {
        }

        return [
            'id'       => $userId,
            'username' => $username,
            'name'     => $displayName !== '' ? $displayName : $username,
            'url'      => $username !== '' ? '/profile/' . $username : null,
        ];
    }

    /**
     * Get categories assigned to a post, formatted for templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPostCategories(int $postId, ?string $urlPrefix = null): array
    {
        $prefix = $urlPrefix ?? $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $all = $this->app->blog()->listCategories();
        $ids = $this->app->blog()->getPostCategoryIds($postId);
        $items = [];

        foreach ($all as $category) {
            if (in_array((int) $category->id, $ids, true)) {
                $items[] = [
                    'id'   => (int) $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'url'  => $prefix . '/category/' . $category->slug,
                ];
            }
        }

        return $items;
    }

    /**
     * Get tags assigned to a post, formatted for templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPostTags(int $postId, ?string $urlPrefix = null): array
    {
        $prefix = $urlPrefix ?? $this->app->pluginLoader()->routePrefix('pubvana/blog');
        $names = $this->app->blog()->getPostTagNames($postId);
        $all = $this->app->blog()->listTags();
        $items = [];

        foreach ($all as $tag) {
            if (in_array($tag->name, $names, true)) {
                $items[] = [
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'url'  => $prefix . '/tag/' . $tag->slug,
                ];
            }
        }

        return $items;
    }

    /**
     * Build pagination data for the shared pagination.tpl partial.
     *
     * URLs use clean, crawlable paths: /blog for page 1 and
     * /blog/page/@page for subsequent pages. Returns prev_url,
     * next_url and the page-number list expected by the partial.
     */
    /**
     * @param array<string, mixed> $result List result as returned by BlogService::listPosts()
     * @return array<string, mixed>|null
     */
    private function buildPagination(array $result, string $baseUrl): ?array
    {
        $page = (int) ($result['page'] ?? 1);
        $perPage = (int) ($result['per_page'] ?? 10);
        $total = (int) ($result['total'] ?? 0);
        $pages = (int) ceil($total / max($perPage, 1));

        if ($pages <= 1) {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/') ?: '/';

        $pageUrl = function (int $n) use ($baseUrl): string {
            return $n === 1 ? $baseUrl : $baseUrl . '/page/' . $n;
        };

        $items = [];
        for ($n = 1; $n <= $pages; $n++) {
            $items[] = [
                'number' => $n,
                'url'    => $pageUrl($n),
                'active' => $n === $page,
            ];
        }

        return [
            'current'  => $page,
            'total'    => $pages,
            'prev_url' => $page > 1 ? $pageUrl($page - 1) : null,
            'next_url' => $page < $pages ? $pageUrl($page + 1) : null,
            'pages'    => $items,
        ];
    }

    /**
     * Get a public asset URL.
     */
    private function publicAssetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Whether to show the visible AI-assistance disclosure for this post.
     */
    private function aiDisclosure(int|bool $aiGenerated): bool
    {
        if (empty($aiGenerated)) {
            return false;
        }

        return (bool) $this->app->settings()->get('Seo.ai_disclosure_enabled', true);
    }

    /**
     * Generate RSS 2.0 feed.
     */
    public function rss(): void
    {
        $posts = $this->app->blog()->listPublished(1, 20);
        $xml = $this->generateRss($posts);

        $this->app->response()->header('Content-Type', 'application/rss+xml; charset=utf-8');
        $this->app->halt(200, $xml);
    }

    /**
     * Generate Atom feed.
     */
    public function atom(): void
    {
        $posts = $this->app->blog()->listPublished(1, 20);
        $xml = $this->generateAtom($posts);

        $this->app->response()->header('Content-Type', 'application/atom+xml; charset=utf-8');
        $this->app->halt(200, $xml);
    }

    /**
     * Generate RSS 2.0 XML from posts.
     *
     * @param array{items: array<int, Post>, total: int, page: int, per_page: int} $posts
     */
    private function generateRss(array $posts): string
    {
        $siteName = $this->app->settings()->get('CMS.siteName') ?? 'Blog';
        $siteUrl = $this->app->settings()->get('CMS.siteUrl') ?? $this->app->get('flight.base_url');
        $siteDescription = $this->app->settings()->get('CMS.siteByline') ?? '';
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '<link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        $xml .= '<description>' . htmlspecialchars($siteDescription) . '</description>' . "\n";
        $xml .= '<language>en-us</language>' . "\n";
        $xml .= '<atom:link href="' . htmlspecialchars($siteUrl . '/feed') . '" rel="self" type="application/rss+xml"/>' . "\n";

        foreach ($posts['items'] as $post) {
            $postUrl = $siteUrl . $prefix . '/' . $post->slug;
            $categories = $this->getPostCategories((int) $post->id);
            $tags = $this->getPostTags((int) $post->id);

            $xml .= '<item>' . "\n";
            $xml .= '<title>' . htmlspecialchars($post->title) . '</title>' . "\n";
            $xml .= '<link>' . htmlspecialchars($postUrl) . '</link>' . "\n";
            $xml .= '<guid isPermaLink="true">' . htmlspecialchars($postUrl) . '</guid>' . "\n";
            $xml .= '<pubDate>' . $this->feedDate($post->published_at, 'r') . '</pubDate>' . "\n";

            $author = $this->getAuthor($post);
            if ($author) {
                $xml .= '<author>' . htmlspecialchars($author['name']) . '</author>' . "\n";
            }

            foreach ($categories as $cat) {
                $xml .= '<category>' . htmlspecialchars($cat['name']) . '</category>' . "\n";
            }
            foreach ($tags as $tag) {
                $xml .= '<category>' . htmlspecialchars($tag['name']) . '</category>' . "\n";
            }

            $content = $post->content ?? $post->excerpt ?? '';
            $xml .= '<description>' . htmlspecialchars($content) . '</description>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Generate Atom XML from posts.
     *
     * @param array{items: array<int, Post>, total: int, page: int, per_page: int} $posts
     */
    private function generateAtom(array $posts): string
    {
        $siteName = $this->app->settings()->get('CMS.siteName') ?? 'Blog';
        $siteUrl = $this->app->settings()->get('CMS.siteUrl') ?? $this->app->get('flight.base_url');
        $prefix = $this->app->pluginLoader()->routePrefix('pubvana/blog');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '<link href="' . htmlspecialchars($siteUrl) . '"/>' . "\n";
        $xml .= '<link href="' . htmlspecialchars($siteUrl . '/atom.xml') . '" rel="self" type="application/atom+xml"/>' . "\n";
        $xml .= '<id>' . htmlspecialchars($siteUrl . '/atom.xml') . '</id>' . "\n";

        if (!empty($posts['items'])) {
            $firstPost = $posts['items'][0];
            $xml .= '<updated>' . $this->feedDate($firstPost->updated_at ?? $firstPost->published_at, 'c') . '</updated>' . "\n";
        }

        foreach ($posts['items'] as $post) {
            $postUrl = $siteUrl . $prefix . '/' . $post->slug;
            $categories = $this->getPostCategories((int) $post->id);
            $tags = $this->getPostTags((int) $post->id);

            $xml .= '<entry>' . "\n";
            $xml .= '<title>' . htmlspecialchars($post->title) . '</title>' . "\n";
            $xml .= '<link href="' . htmlspecialchars($postUrl) . '"/>' . "\n";
            $xml .= '<id>' . htmlspecialchars($postUrl) . '</id>' . "\n";
            $xml .= '<published>' . $this->feedDate($post->published_at, 'c') . '</published>' . "\n";
            $xml .= '<updated>' . $this->feedDate($post->updated_at ?? $post->published_at, 'c') . '</updated>' . "\n";

            $author = $this->getAuthor($post);
            if ($author) {
                $xml .= '<author><name>' . htmlspecialchars($author['name']) . '</name></author>' . "\n";
            }

            foreach ($categories as $cat) {
                $xml .= '<category term="' . htmlspecialchars($cat['name']) . '"/>' . "\n";
            }
            foreach ($tags as $tag) {
                $xml .= '<category term="' . htmlspecialchars($tag['name']) . '"/>' . "\n";
            }

            $content = $post->content ?? $post->excerpt ?? '';
            $xml .= '<content type="html">' . htmlspecialchars($content) . '</content>' . "\n";
            $xml .= '</entry>' . "\n";
        }

        $xml .= '</feed>';

        return $xml;
    }

    /**
     * Format a nullable timestamp for feed output, falling back to empty string.
     */
    private function feedDate(?string $date, string $format): string
    {
        $ts = strtotime((string) $date);
        return $ts === false ? '' : date($format, $ts);
    }
}
