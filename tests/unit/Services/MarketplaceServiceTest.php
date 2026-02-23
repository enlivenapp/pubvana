<?php

use App\Services\MarketplaceService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MarketplaceServiceTest extends CIUnitTestCase
{
    private MarketplaceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketplaceService();
    }

    // fetchAll ---------------------------------------------------------------

    public function testFetchAllReturnsArray(): void
    {
        $items = $this->service->fetchAll();
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
    }

    public function testFetchAllItemsAreObjects(): void
    {
        $items = $this->service->fetchAll();
        foreach ($items as $item) {
            $this->assertIsObject($item);
        }
    }

    public function testFetchAllItemsHaveInstalledVersionKey(): void
    {
        $items = $this->service->fetchAll();
        foreach ($items as $item) {
            $this->assertObjectHasProperty('installed_version', $item);
        }
    }

    public function testFetchAllItemsHaveItemType(): void
    {
        $items = $this->service->fetchAll();
        foreach ($items as $item) {
            $this->assertObjectHasProperty('item_type', $item);
            $this->assertContains($item->item_type, ['theme', 'widget']);
        }
    }

    public function testFetchAllContainsThemesAndWidgets(): void
    {
        $items = $this->service->fetchAll();
        $types = array_unique(array_map(fn($i) => $i->item_type, $items));
        $this->assertContains('theme', $types);
        $this->assertContains('widget', $types);
    }

    // fetchThemes / fetchWidgets (via fetchAll filter) -----------------------

    public function testFetchAllThemesOnlyContainThemes(): void
    {
        $items = $this->service->fetchAll();
        $themes = array_filter($items, fn($i) => $i->item_type === 'theme');
        $this->assertNotEmpty($themes);
        foreach ($themes as $t) {
            $this->assertSame('theme', $t->item_type);
        }
    }

    public function testFetchAllWidgetsOnlyContainWidgets(): void
    {
        $items = $this->service->fetchAll();
        $widgets = array_filter($items, fn($i) => $i->item_type === 'widget');
        $this->assertNotEmpty($widgets);
        foreach ($widgets as $w) {
            $this->assertSame('widget', $w->item_type);
        }
    }

    // installFree guard clauses ---------------------------------------------

    public function testInstallFreeRejectsNonPubvanaHost(): void
    {
        $result = $this->service->installFree(
            'https://evil.com/download/malware',
            'theme',
            'malware'
        );
        $this->assertFalse($result);
    }

    public function testInstallFreeRejectsPathTraversalFolder(): void
    {
        $result = $this->service->installFree(
            'https://pubvana.net/marketplace/download/test',
            'theme',
            '../../../etc/passwd'
        );
        $this->assertFalse($result);
    }

    public function testInstallFreeRejectsUppercaseInFolder(): void
    {
        $result = $this->service->installFree(
            'https://pubvana.net/marketplace/download/test',
            'theme',
            'MyTheme'
        );
        $this->assertFalse($result);
    }

    public function testInstallFreeRejectsEmptyFolder(): void
    {
        $result = $this->service->installFree(
            'https://pubvana.net/marketplace/download/test',
            'theme',
            ''
        );
        $this->assertFalse($result);
    }

    // refreshCache ----------------------------------------------------------

    public function testRefreshCacheDoesNotThrow(): void
    {
        // Should run without exception
        $this->service->refreshCache();
        $this->assertTrue(true);
    }
}
