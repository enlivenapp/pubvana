<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Pages\Services;

use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Pages\Models\PageRevision;

/**
 * Service layer for pages — CRUD, published lookups, and host integrations.
 *
 * Registered on the app engine as `pages` by the Pages plugin, so any
 * consumer reaches pages through `$app->pages()` rather than touching
 * the model directly.
 *
 * @package Pubvana\Plugins\Pages\Services
 */
class PagesService
{
    private Page $pageModel;
    private PageRevision $revisionModel;
    private \PDO $pdo;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
    */
    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pageModel = new Page($pdo);
        $this->revisionModel = new PageRevision($pdo);
        $this->pdo = $pdo;
        $this->config = $config;
    }

    // ─── Pages ──────────────────────────────────────────────────────────

    /**
     * @return array{items: Page[], total: int, page: int, per_page: int}
     */
    public function listPages(int $page = 1, int $perPage = 20): array
    {
        return [
            'items'    => $this->pageModel->findAllPaginated($page, $perPage),
            'total'    => $this->pageModel->countAll(),
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return Page[]
     */
    public function listPublished(int $limit = 100): array
    {
        return $this->pageModel->findAllPublished($limit);
    }

    public function findPage(int $id): ?Page
    {
        return $this->pageModel->findById($id);
    }

    public function findPageBySlug(string $slug): ?Page
    {
        return $this->pageModel->findBySlug($slug);
    }

    public function pageSlugExists(string $slug, ?int $excludeId = null): bool
    {
        return $this->pageModel->slugExists($slug, $excludeId);
    }

    /**
     * @param array<string, mixed> $data
    */
    public function createPage(array $data, int $userId): Page
    {
        $page = $this->pageModel->createPage(
            (string) ($data['title'] ?? ''),
            (string) ($data['content'] ?? ''),
            $userId,
            !empty($data['ai_generated']) ? 1 : 0
        );

        $page->updatePage([
            'status'         => (string) ($data['status'] ?? 'draft'),
            'allow_comments' => !empty($data['allow_comments']) ? 1 : 0,
        ]);

        $this->revisionModel->createFromPage($page, $userId);
        $this->pruneRevisions((int) $page->id);

        return $page;
    }

    /**
     * @param array<string, mixed> $data
    */
    public function updatePage(int $id, array $data, ?int $userId = null): ?Page
    {
        $page = $this->pageModel->findById($id);
        if ($page === null) {
            return null;
        }

        $authorId = $userId ?? (int) $page->created_by;
        $this->revisionModel->createFromPage($page, $authorId);

        $page->updatePage([
            'title'          => $data['title'] ?? $page->title,
            'content'        => $data['content'] ?? $page->content,
            'status'         => $data['status'] ?? $page->status,
            'allow_comments' => !empty($data['allow_comments']) ? 1 : 0,
        ]);
        $this->pruneRevisions($id);

        return $page;
    }

    public function deletePage(int $id): bool
    {
        $page = $this->pageModel->findById($id);
        if ($page === null) {
            return false;
        }

        $page->softDelete();
        return true;
    }

    // ─── Revisions ──────────────────────────────────────────────────────

    /**
     * @return array<int, \Pubvana\Plugins\Pages\Models\PageRevision>
    */
    public function getRevisions(int $pageId): array
    {
        return $this->revisionModel->getForPage($pageId);
    }

    public function restoreRevision(int $pageId, int $revisionId, int $userId): ?Page
    {
        $page = $this->pageModel->findById($pageId);
        if ($page === null) {
            return null;
        }

        $revision = $this->revisionModel->findById($revisionId);
        if ($revision === null || (int) $revision->page_id !== $pageId) {
            return null;
        }

        $page->updatePage([
            'title'          => $revision->title,
            'content'        => $revision->content,
            'status'         => $revision->status,
            'allow_comments' => (int) $revision->allow_comments,
        ]);

        $this->revisionModel->createFromPage($page, $userId);
        $this->pruneRevisions($pageId);

        return $page;
    }

    private function pruneRevisions(int $pageId): void
    {
        $max = $this->config['max_revisions'] ?? 15;
        $this->revisionModel->pruneForPage($pageId, $max);
    }

    /**
     * Published pages as an id => title map for the Settings homepage selector.
     *
     * @return array<int, string> page id => page title
     */
    public function publishedOptions(): array
    {
        return $this->pageModel->getPublishedOptions();
    }

    // ─── Host Integrations ───────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
    */
    public function searchProvider(string $term): array
    {
        return $this->pageModel->searchContent($term, $this->routePrefix());
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function navLinkableItems(): array
    {
        $stmt = $this->pdo->query(
            "SELECT title, slug FROM pages WHERE status = 'published' AND deleted_at IS NULL ORDER BY title"
        );

        $items = [];
        if ($stmt === false) {
            return $items;
        }
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = [
                'label' => $row['title'],
                'url'   => $this->routePrefix() . '/' . $row['slug'],
            ];
        }
        return $items;
    }

    /**
     * Enumerate published pages for the Comments host contract.
     *
     * @return array<int, array{type: string, id: int, title: string, url: string, allow_comments: bool}>
     */
    public function commentHostItems(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, title, slug, allow_comments FROM pages WHERE status = 'published' AND deleted_at IS NULL ORDER BY title"
        );

        $items = [];
        if ($stmt === false) {
            return $items;
        }
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = [
                'type'           => 'page',
                'id'             => (int) $row['id'],
                'title'          => $row['title'],
                'url'            => $this->routePrefix() . '/' . $row['slug'],
                'allow_comments' => (bool) $row['allow_comments'],
            ];
        }
        return $items;
    }

    // ─── Dashboard ──────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
    */
    public function dashboardCards(): array
    {
        return [[
            'id'          => 'total-pages',
            'label'       => 'Pages',
            'value'       => $this->pageModel->countAll(),
            'icon'        => 'ti-file',
            'tone'        => 'primary',
            'group'       => 'content',
            'href'        => $this->routePrefix(),
            'description' => 'Static pages on the site.',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
    */
    public function dashboardSections(): array
    {
        $recent = $this->pageModel->findAllPaginated(1, 5);
        $prefix = $this->routePrefix();
        $items = [];

        foreach ($recent as $page) {
            $items[] = [
                'label'    => $page->title,
                'meta'     => ucfirst((string) $page->status) . ' · ' . (($ts = strtotime((string) $page->created_at)) === false ? '' : date('M j, Y g:ia', $ts)),
                'href'     => $prefix . '/' . (int) $page->id . '/edit',
                'emphasis' => $page->status === 'published' ? 'success' : 'secondary',
            ];
        }

        return [[
            'id'          => 'recent-pages',
            'title'       => 'Recent Pages',
            'type'        => 'list',
            'icon'        => 'ti-file-text',
            'group'       => 'content',
            'href'        => $prefix,
            'empty_state' => 'No pages have been created yet.',
            'items'       => $items,
        ]];
    }

    /**
     * The public route prefix for this plugin (leading slash, no trailing slash).
     */
    private function routePrefix(): string
    {
        return rtrim((string) ($this->config['route_prefix'] ?? ''), '/');
    }
}