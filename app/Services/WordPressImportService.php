<?php

namespace App\Services;

/**
 * WordPressImportService
 *
 * Parses a WordPress WXR (XML) export file and imports content into Pubvana.
 * Import order: authors → categories → tags → posts → pages → comments
 */
class WordPressImportService
{
    protected bool  $dryRun  = false;
    protected array $results = [
        'authors'    => ['created' => 0, 'skipped' => 0],
        'categories' => ['created' => 0, 'skipped' => 0],
        'tags'       => ['created' => 0, 'skipped' => 0],
        'posts'      => ['created' => 0, 'skipped' => 0],
        'pages'      => ['created' => 0, 'skipped' => 0],
        'comments'   => ['created' => 0, 'skipped' => 0],
        'errors'     => [],
    ];

    // Map WP login → Pubvana user_id
    protected array $authorMap   = [];
    // Map WP category nicename → Pubvana category_id
    protected array $categoryMap = [];
    // Map WP tag slug → Pubvana tag_id
    protected array $tagMap      = [];
    // Map WP post_id → Pubvana post_id
    protected array $postMap     = [];

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Parse and import the WXR file at $path.
     */
    public function import(string $path): array
    {
        if (! file_exists($path)) {
            $this->results['errors'][] = 'File not found: ' . $path;
            return $this->results;
        }

        // Load file contents then parse as string to prevent XXE injection.
        // LIBXML_NONET blocks network fetches for any entity references.
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->results['errors'][] = 'Failed to read file: ' . $path;
            return $this->results;
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        unset($contents); // free memory
        if (! $xml) {
            $this->results['errors'][] = 'Failed to parse XML. Ensure the file is a valid WordPress WXR export.';
            return $this->results;
        }

        $ns      = $xml->getNamespaces(true);
        $channel = $xml->channel;

        $this->importAuthors($channel, $ns);
        $this->importCategories($channel, $ns);
        $this->importTags($channel, $ns);
        $this->importItems($channel, $ns);

        return $this->results;
    }

    // -------------------------------------------------------------------------
    // Authors
    // -------------------------------------------------------------------------
    protected function importAuthors(\SimpleXMLElement $channel, array $ns): void
    {
        $wpNs = $ns['wp'] ?? 'http://wordpress.org/export/1.2/';

        foreach ($channel->children($wpNs)->author as $author) {
            $login       = (string) $author->author_login;
            $email       = (string) $author->author_email;
            $displayName = (string) $author->author_display_name;

            if (! $login) {
                continue;
            }

            $db       = db_connect();
            $existing = $db->table('users u')
                ->select('u.id')
                ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
                ->where('u.username', $login)
                ->orWhere('ai.secret', $email)
                ->get()->getRowObject();

            if ($existing) {
                $this->authorMap[$login] = (int) $existing->id;
                $this->results['authors']['skipped']++;
                continue;
            }

            if (! $this->dryRun) {
                $password = bin2hex(random_bytes(16));
                $userId   = $db->table('users')->insert([
                    'username'   => $login,
                    'active'     => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], true);

                $db->table('auth_identities')->insert([
                    'user_id'    => $userId,
                    'type'       => 'email_password',
                    'name'       => $email,
                    'secret'     => $email,
                    'secret2'    => password_hash($password, PASSWORD_DEFAULT),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $db->table('auth_groups_users')->insert([
                    'user_id'    => $userId,
                    'group'      => 'author',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                if ($displayName) {
                    $db->table('author_profiles')->insert([
                        'user_id'      => $userId,
                        'display_name' => $displayName,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }

                $this->authorMap[$login] = (int) $userId;
            }
            $this->results['authors']['created']++;
        }
    }

    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------
    protected function importCategories(\SimpleXMLElement $channel, array $ns): void
    {
        $wpNs = $ns['wp'] ?? 'http://wordpress.org/export/1.2/';

        // Collect all first so we can resolve parent hierarchy
        $cats = [];
        foreach ($channel->children($wpNs)->category as $cat) {
            $nicename = (string) $cat->category_nicename;
            $name     = (string) $cat->cat_name;
            $parent   = (string) $cat->category_parent;
            if ($nicename) {
                $cats[$nicename] = ['name' => $name, 'slug' => $nicename, 'parent' => $parent];
            }
        }

        // Insert in two passes to handle parent references
        $this->insertCategories($cats, '', 0);
        // Second pass for any unresolved parents
        foreach ($cats as $slug => $cat) {
            if (! isset($this->categoryMap[$slug])) {
                $this->insertCategoryRow($cat['name'], $slug, 0);
            }
        }
    }

    protected function insertCategories(array $cats, string $parentSlug, int $parentId): void
    {
        foreach ($cats as $slug => $cat) {
            if ($cat['parent'] !== $parentSlug) {
                continue;
            }
            if (isset($this->categoryMap[$slug])) {
                continue;
            }
            $resolvedParent = $parentId;
            if ($cat['parent'] && isset($this->categoryMap[$cat['parent']])) {
                $resolvedParent = $this->categoryMap[$cat['parent']];
            }
            $this->insertCategoryRow($cat['name'], $slug, $resolvedParent);
            // Insert children of this category
            $this->insertCategories($cats, $slug, $this->categoryMap[$slug] ?? 0);
        }
    }

    protected function insertCategoryRow(string $name, string $slug, int $parentId): void
    {
        $db       = db_connect();
        $existing = $db->table('categories')->where('slug', $slug)->get()->getRowObject();

        if ($existing) {
            $this->categoryMap[$slug] = (int) $existing->id;
            $this->results['categories']['skipped']++;
            return;
        }

        if (! $this->dryRun) {
            $id = $db->table('categories')->insert([
                'name'      => $name,
                'slug'      => $slug,
                'parent_id' => $parentId ?: null,
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ], true);
            $this->categoryMap[$slug] = (int) $id;
        }
        $this->results['categories']['created']++;
    }

    // -------------------------------------------------------------------------
    // Tags
    // -------------------------------------------------------------------------
    protected function importTags(\SimpleXMLElement $channel, array $ns): void
    {
        $wpNs = $ns['wp'] ?? 'http://wordpress.org/export/1.2/';

        foreach ($channel->children($wpNs)->tag as $tag) {
            $slug = (string) $tag->tag_slug;
            $name = (string) $tag->tag_name;
            if (! $slug) {
                continue;
            }

            $db       = db_connect();
            $existing = $db->table('tags')->where('slug', $slug)->get()->getRowObject();

            if ($existing) {
                $this->tagMap[$slug] = (int) $existing->id;
                $this->results['tags']['skipped']++;
                continue;
            }

            if (! $this->dryRun) {
                $id = $db->table('tags')->insert([
                    'name'       => $name,
                    'slug'       => $slug,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], true);
                $this->tagMap[$slug] = (int) $id;
            }
            $this->results['tags']['created']++;
        }
    }

    // -------------------------------------------------------------------------
    // Items (posts + pages)
    // -------------------------------------------------------------------------
    protected function importItems(\SimpleXMLElement $channel, array $ns): void
    {
        $wpNs      = $ns['wp']      ?? 'http://wordpress.org/export/1.2/';
        $contentNs = $ns['content'] ?? 'http://purl.org/rss/1.0/modules/content/';
        $excerptNs = $ns['excerpt'] ?? 'http://wordpress.org/export/1.2/excerpt/';
        $dcNs      = $ns['dc']      ?? 'http://purl.org/dc/elements/1.1/';

        $comments = []; // Collect for after posts

        foreach ($channel->item as $item) {
            $wp = $item->children($wpNs);

            $postType   = (string) $wp->post_type;
            $postStatus = (string) $wp->status;
            $wpPostId   = (int)    $wp->post_id;
            $title      = (string) $item->title;
            $slug       = (string) $wp->post_name;
            $content    = (string) $item->children($contentNs)->encoded;
            $excerpt    = (string) $item->children($excerptNs)->encoded;
            $creator    = (string) $item->children($dcNs)->creator;
            $pubDate    = (string) $wp->post_date;

            $status = $postStatus === 'publish' ? 'published' : ($postStatus === 'draft' ? 'draft' : 'draft');

            // Collect categories and tags for this item
            $itemCatIds = [];
            $itemTagIds = [];
            foreach ($item->category as $cat) {
                $domain   = (string) $cat['domain'];
                $nicename = (string) $cat['nicename'];
                if ($domain === 'category' && $nicename && isset($this->categoryMap[$nicename])) {
                    $itemCatIds[] = $this->categoryMap[$nicename];
                } elseif ($domain === 'post_tag' && $nicename && isset($this->tagMap[$nicename])) {
                    $itemTagIds[] = $this->tagMap[$nicename];
                }
            }

            if ($postType === 'post') {
                $this->importPost($wpPostId, $title, $slug, $content, $excerpt, $status, $creator, $pubDate, $itemCatIds, $itemTagIds);
            } elseif ($postType === 'page') {
                $this->importPage($wpPostId, $title, $slug, $content, $status, $pubDate);
            } else {
                continue;
            }

            // Collect comments
            foreach ($wp->comment as $comment) {
                $comments[] = ['wp_post_id' => $wpPostId, 'post_type' => $postType, 'comment' => $comment, 'wpNs' => $wpNs];
            }
        }

        $this->importComments($comments);
    }

    protected function importPost(
        int $wpPostId, string $title, string $slug, string $content,
        string $excerpt, string $status, string $creator, string $pubDate,
        array $catIds, array $tagIds
    ): void {
        $db       = db_connect();
        $authorId = $this->authorMap[$creator] ?? auth()->id();

        $slug = $this->uniqueSlug($slug ?: slug_from_title($title), 'posts');

        if (! $this->dryRun) {
            $id = $db->table('posts')->insert([
                'title'        => $title,
                'slug'         => $slug,
                'content'      => $content,
                'content_type' => 'html',
                'excerpt'      => $excerpt ?: null,
                'status'       => $status,
                'author_id'    => $authorId,
                'lang'         => 'en',
                'published_at' => $status === 'published' ? ($pubDate ?: date('Y-m-d H:i:s')) : null,
                'created_at'   => $pubDate ?: date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], true);

            $this->postMap[$wpPostId] = (int) $id;

            foreach ($catIds as $catId) {
                $db->table('posts_to_categories')->ignore(true)->insert(['post_id' => $id, 'category_id' => $catId]);
            }
            foreach ($tagIds as $tagId) {
                $db->table('tags_to_posts')->ignore(true)->insert(['post_id' => $id, 'tag_id' => $tagId]);
            }
        }
        $this->results['posts']['created']++;
    }

    protected function importPage(
        int $wpPostId, string $title, string $slug, string $content,
        string $status, string $pubDate
    ): void {
        $db   = db_connect();
        $slug = $this->uniqueSlug($slug ?: slug_from_title($title), 'pages');

        if (! $this->dryRun) {
            $id = $db->table('pages')->insert([
                'title'        => $title,
                'slug'         => $slug,
                'content'      => $content,
                'content_type' => 'html',
                'status'       => $status,
                'lang'         => 'en',
                'published_at' => $status === 'published' ? ($pubDate ?: date('Y-m-d H:i:s')) : null,
                'created_at'   => $pubDate ?: date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], true);

            $this->postMap[$wpPostId] = (int) $id; // pages share the map for comment linking
        }
        $this->results['pages']['created']++;
    }

    protected function importComments(array $items): void
    {
        foreach ($items as $item) {
            $wpPostId  = $item['wp_post_id'];
            $postType  = $item['post_type'];
            $comment   = $item['comment'];
            $wpNs      = $item['wpNs'];

            $pubvanaPostId = $this->postMap[$wpPostId] ?? null;
            if (! $pubvanaPostId) {
                $this->results['comments']['skipped']++;
                continue;
            }

            $status      = (string) $comment->comment_approved === '1' ? 'approved' : 'pending';
            $authorName  = (string) $comment->comment_author;
            $authorEmail = (string) $comment->comment_author_email;
            $content     = (string) $comment->comment_content;
            $date        = (string) $comment->comment_date;

            if (! $content || ! $authorName) {
                $this->results['comments']['skipped']++;
                continue;
            }

            if (! $this->dryRun) {
                $table = $postType === 'page' ? null : 'comments';
                if ($table) {
                    db_connect()->table($table)->insert([
                        'post_id'      => $pubvanaPostId,
                        'author_name'  => $authorName,
                        'author_email' => $authorEmail,
                        'content'      => $content,
                        'status'       => $status,
                        'created_at'   => $date ?: date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            $this->results['comments']['created']++;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    protected function uniqueSlug(string $slug, string $table): string
    {
        $db   = db_connect();
        $base = $slug;
        $i    = 2;
        while ($db->table($table)->where('slug', $slug)->countAllResults() > 0) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
