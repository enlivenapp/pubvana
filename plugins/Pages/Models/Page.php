<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Models;

/**
 * Page - ActiveRecord model for the pages table.
 *
 * Represents a static page (About, Contact, Terms, etc.).
 * Pages are simple content containers with SEO metadata,
 * draft/published status, and soft-delete support.
 *
 * Schema:
 *   id                - Auto-increment primary key
 *   title             - Page title
 *   slug              - URL-safe slug (unique among non-deleted pages)
 *   content           - HTML content (sanitized on save)
 *   status            - 'draft' or 'published'
 *   allow_comments    - 1 if comments are allowed on this page (per-page toggle)
 *   created_by        - User ID of creator
 *   created_at        - Creation timestamp
 *   updated_at        - Last update timestamp
 *   deleted_at        - Soft-delete timestamp
 *
 * @package Pubvana\Plugins\Pages\Models
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self notEq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @method self startWrap()
 * @method self endWrap(string $op)
 * @property int $cnt Aggregate alias from COUNT(*) selects
 */
class Page extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'pages', $config);
    }

    public int $id;
    public ?string $title = null;
    public ?string $slug = null;
    public ?string $content = null;
    public string $status = 'draft';
    public int $ai_generated = 0;
    public int $allow_comments = 0;
    public int $created_by = 0;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $deleted_at = null;

    // -----------------------------------------------------------------
    // Finders
    // -----------------------------------------------------------------

    /**
     * Find a page by ID (excluding soft-deleted).
     *
     * @param int $id Page ID
     * @return self|null Page or null if not found
     */
    public function findById(int $id): ?self
    {
        $this->reset();
        $this->eq('id', $id)->isNull('deleted_at')->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Find a published page by slug.
     *
     * @param string $slug URL slug
     * @return self|null Published page or null
     */
    public function findBySlug(string $slug): ?self
    {
        $this->reset();
        $this->eq('slug', $slug)
             ->eq('status', 'published')
             ->isNull('deleted_at')
             ->find();
        return $this->isHydrated() ? $this : null;
    }

    /**
     * Check if a slug already exists (excluding a given page ID).
     *
     * @param string $slug Slug to check
     * @param int|null $excludeId Page ID to exclude from check
     * @return bool True if slug is taken
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = new self($this->getDatabaseConnection());
        $query->select('COUNT(*) as cnt')
              ->eq('slug', $slug)
              ->isNull('deleted_at');

        if ($excludeId !== null) {
            $query->notEq('id', $excludeId);
        }

        $result = $query->find();
        return ($result->cnt ?? 0) > 0;
    }

    /**
     * Find all pages with pagination.
     *
     * @param int $page Current page number
     * @param int $perPage Results per page
     * @return self[] Array of pages
     */
    public function findAllPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $model = new self($this->getDatabaseConnection());
        return $model->isNull('deleted_at')
                     ->order('created_at DESC')
                     ->limit($perPage)
                     ->offset($offset)
                     ->findAll();
    }

    /**
* Find published pages ordered by title.
 *
 * Consumed by link collectors (redirect target suggestions, navigation)
     * that need a stable, alphabetized list of live pages.
     *
     * @param int $limit Maximum results
     * @return self[] Array of published pages
     */
    public function findAllPublished(int $limit = 100): array
    {
        $model = new self($this->getDatabaseConnection());
        return $model->eq('status', 'published')
                     ->isNull('deleted_at')
                     ->order('title ASC')
                     ->limit($limit)
                     ->findAll();
    }

    /**
     * Count all non-deleted pages.
     *
     * @return int Total page count
     */
    public function countAll(): int
    {
        $model = new self($this->getDatabaseConnection());
        $model->select('COUNT(*) as cnt')->isNull('deleted_at')->find();
        return (int) ($model->cnt ?? 0);
    }

    /**
     * Published pages as an id => title map, ordered by title.
     *
     * Consumed by the admin Settings form's homepage selector. Lazily
     * fetched from SettingsController rather than at core boot, so the
     * query never runs on a public request.
     *
     * @return array<int, string> page id => page title
     */
    public function getPublishedOptions(): array
    {
        $model = new self($this->getDatabaseConnection());
        $pages = $model->select('id', 'title')
                       ->eq('status', 'published')
                       ->isNull('deleted_at')
                       ->order('title ASC')
                       ->findAll();
        $options = [];
        foreach ($pages as $page) {
            $options[(int) $page->id] = (string) $page->title;
        }
        return $options;
    }

    /**
     * Find published pages matching a search term in title, slug, or content.
     *
     * Supplies normalized content matches for the Search plugin. Ranking is
     * owned by SearchService — this method only finds matching content.
     *
     * @param string $term       Raw search term
     * @param string $urlPrefix  Public route prefix for result URLs
     * @return list<array<string, mixed>>
     */
    public function searchContent(string $term, string $urlPrefix): array
    {
        $pages = (new self($this->getDatabaseConnection()))
            ->startWrap()
                ->like('title', '%' . $term . '%')
                ->like('slug', '%' . $term . '%', 'or')
                ->like('content', '%' . $term . '%', 'or')
            ->endWrap('OR')
            ->eq('status', 'published')
            ->isNull('deleted_at')
            ->findAll();

        $results = [];
        foreach ($pages as $page) {
            $stripped = html_entity_decode(strip_tags((string) ($page->content ?? '')), ENT_QUOTES, 'UTF-8');
            $len = mb_strlen($stripped);
            $pos = mb_stripos($stripped, $term);
            if ($pos !== false) {
                $start = max(0, $pos - 80);
                $excerpt = ($start > 0 ? '...' : '') . mb_substr($stripped, $start, 200) . ($start + 200 < $len ? '...' : '');
            } else {
                $excerpt = mb_substr($stripped, 0, 200) . ($len > 200 ? '...' : '');
            }

            $results[] = [
                'id'           => (int) $page->id,
                'title'        => (string) $page->title,
                'url'          => $urlPrefix . '/' . $page->slug,
                'excerpt'      => $excerpt,
                'content_type' => 'Page',
                'published_at' => $page->created_at,
            ];
        }

        return $results;
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    /**
     * Create a new page with auto-generated slug.
     *
     * @param string $title Page title
     * @param string $content HTML content
     * @param int $createdBy User ID of creator
     * @return self The created page
     */
    public function createPage(string $title, string $content, int $createdBy, int $ai_generated = 0): self
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->title = $title;
        $this->slug = $this->generateSlug($title);
        $this->content = $content;
        $this->status = 'draft';
        $this->created_by = $createdBy;
        $this->ai_generated = $ai_generated;
        $this->created_at = $now;
        $this->updated_at = $now;
        $this->insert();

        return $this;
    }

    /**
     * Update this page.
     *
     * @param array{title?: string, content?: string|null, status?: string, allow_comments?: mixed, ai_generated?: mixed} $data Fields to update
     */
    public function updatePage(array $data): void
    {
        if (isset($data['title'])) {
            $this->title = $data['title'];
        }
        if (isset($data['content'])) {
            $this->content = $data['content'];
        }
        if (isset($data['status'])) {
            $this->status = $data['status'];
        }
        if (isset($data['allow_comments'])) {
            $this->allow_comments = $data['allow_comments'] ? 1 : 0;
        }
        if (isset($data['ai_generated'])) {
            $this->ai_generated = $data['ai_generated'] ? 1 : 0;
        }

        $this->updated_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    /**
     * Soft-delete this page.
     */
    public function softDelete(): void
    {
        $this->deleted_at = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->save();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Generate a URL-safe slug from a title.
     *
     * @param string $title Source text
     * @return string URL-safe slug
     */
    protected function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = str_replace('&', 'and', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug) ?? '';
        $slug = preg_replace('/[\s]+/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $original = $slug;
        $counter = 1;
        while ($this->slugExists($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
