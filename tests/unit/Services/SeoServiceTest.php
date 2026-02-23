<?php

use App\Services\SeoService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class SeoServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private SeoService $seo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seo = new SeoService();
    }

    // getMeta ----------------------------------------------------------------

    public function testGetMetaReturnsAllKeys(): void
    {
        $entity = (object) [
            'title'            => 'My Post',
            'meta_title'       => null,
            'meta_description' => null,
        ];

        $meta = $this->seo->getMeta($entity);

        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('og_title', $meta);
        $this->assertArrayHasKey('og_description', $meta);
        $this->assertArrayHasKey('og_image', $meta);
    }

    public function testGetMetaUsesMetaTitleOverTitle(): void
    {
        $entity = (object) [
            'title'            => 'Post Title',
            'meta_title'       => 'Custom Meta Title',
            'meta_description' => '',
        ];

        $meta = $this->seo->getMeta($entity);

        $this->assertStringContainsString('Custom Meta Title', $meta['title']);
    }

    public function testGetMetaFallsBackToTitle(): void
    {
        $entity = (object) [
            'title'            => 'Fallback Title',
            'meta_title'       => null,
            'meta_description' => null,
        ];

        $meta = $this->seo->getMeta($entity);

        $this->assertStringContainsString('Fallback Title', $meta['title']);
    }

    public function testGetMetaFeaturedImageProducesOgImage(): void
    {
        $entity = (object) [
            'title'            => 'Post',
            'meta_title'       => null,
            'meta_description' => null,
            'featured_image'   => 'uploads/test.jpg',
        ];

        $meta = $this->seo->getMeta($entity);

        $this->assertStringContainsString('uploads/test.jpg', $meta['og_image']);
    }

    public function testGetMetaNoFeaturedImageEmptyOgImage(): void
    {
        $entity = (object) [
            'title'            => 'Post',
            'meta_title'       => null,
            'meta_description' => null,
        ];

        $meta = $this->seo->getMeta($entity);

        $this->assertSame('', $meta['og_image']);
    }

    // getDefaultMeta ---------------------------------------------------------

    public function testGetDefaultMetaReturnsAllKeys(): void
    {
        $meta = $this->seo->getDefaultMeta();

        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('og_title', $meta);
        $this->assertArrayHasKey('og_description', $meta);
        $this->assertArrayHasKey('og_image', $meta);
    }

    public function testGetDefaultMetaOgImageAlwaysEmpty(): void
    {
        $meta = $this->seo->getDefaultMeta();
        $this->assertSame('', $meta['og_image']);
    }

    public function testGetDefaultMetaTitleIsString(): void
    {
        $meta = $this->seo->getDefaultMeta();
        $this->assertIsString($meta['title']);
        $this->assertNotEmpty($meta['title']);
    }
}
