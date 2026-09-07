<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Pages;

use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Plugins\Pages\Models\Page;
use Pubvana\Plugins\Pages\Services\PagesService;
use PHPUnit\Framework\TestCase;

/**
 * PagesService URL derivation tests on in-memory SQLite.
 *
 * Every public URL the service builds (nav linkable, comments host,
 * search provider, dashboard hrefs) must come from the route_prefix
 * config, never from a literal.
 */
#[CoversClass(PagesService::class)]
final class PagesServiceUrlTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec(
            'CREATE TABLE pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                slug TEXT,
                content TEXT,
                status TEXT NOT NULL DEFAULT "draft",
                ai_generated INTEGER NOT NULL DEFAULT 0,
                allow_comments INTEGER NOT NULL DEFAULT 0,
                created_by INTEGER NOT NULL DEFAULT 0,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )'
        );
    }

    private function service(string $prefix = '/page'): PagesService
    {
        return new PagesService($this->pdo, ['route_prefix' => $prefix]);
    }

    private function insertPage(string $title, string $slug, int $allowComments = 1): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pages (title, slug, content, status, allow_comments, created_at)
             VALUES (:title, :slug, :content, "published", :allow, "2026-01-01 00:00:00")'
        );
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => '<p>' . $title . ' body text</p>',
            'allow' => $allowComments,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function testNavLinkableItemsUseRoutePrefix(): void
    {
        $this->insertPage('About', 'about');

        $items = $this->service()->navLinkableItems();

        self::assertSame('About', $items[0]['label']);
        self::assertSame('/page/about', $items[0]['url']);
    }

    public function testCommentHostItemsUseRoutePrefix(): void
    {
        $id = $this->insertPage('About', 'about');

        $items = $this->service()->commentHostItems();

        self::assertSame('page', $items[0]['type']);
        self::assertSame($id, $items[0]['id']);
        self::assertSame('/page/about', $items[0]['url']);
        self::assertTrue($items[0]['allow_comments']);
    }

    public function testSearchProviderUrlsUseRoutePrefix(): void
    {
        $this->insertPage('About', 'about');

        $results = $this->service()->searchProvider('About');

        self::assertSame('/page/about', $results[0]['url']);
        self::assertSame('Page', $results[0]['content_type']);
    }

    public function testDashboardCardHrefIsBarePrefix(): void
    {
        $cards = $this->service()->dashboardCards();

        self::assertSame('/page', $cards[0]['href']);
    }

    public function testDashboardSectionHrefsUseRoutePrefix(): void
    {
        $id = $this->insertPage('About', 'about');

        $sections = $this->service()->dashboardSections();

        self::assertSame('/page', $sections[0]['href']);
        self::assertSame('/page/' . $id . '/edit', $sections[0]['items'][0]['href']);
    }

    public function testCustomPrependChangesEveryUrl(): void
    {
        $this->insertPage('About', 'about');

        $service = $this->service('/static');

        self::assertSame('/static/about', $service->navLinkableItems()[0]['url']);
        self::assertSame('/static/about', $service->commentHostItems()[0]['url']);
        self::assertSame('/static/about', $service->searchProvider('About')[0]['url']);
        self::assertSame('/static', $service->dashboardCards()[0]['href']);
        self::assertSame('/static', $service->dashboardSections()[0]['href']);
    }

    public function testSearchContentBuildsUrlFromPrefixArgument(): void
    {
        $this->insertPage('About', 'about');

        $page = new Page($this->pdo);
        $results = $page->searchContent('About', '/docs');

        self::assertSame('/docs/about', $results[0]['url']);
    }
}
