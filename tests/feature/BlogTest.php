<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class BlogTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    // Blog index -------------------------------------------------------------

    public function testBlogIndexReturns200(): void
    {
        $result = $this->get('/');
        $result->assertStatus(200);
    }

    public function testBlogRouteReturns200(): void
    {
        $result = $this->get('blog');
        $result->assertStatus(200);
    }

    // Post page --------------------------------------------------------------

    public function testPublishedPostReturns200(): void
    {
        $result = $this->get('blog/hello-world');
        $result->assertStatus(200);
    }

    public function testDraftPostReturns404(): void
    {
        $result = $this->get('blog/a-draft-post');
        $result->assertStatus(404);
    }

    public function testMissingPostReturns404(): void
    {
        $result = $this->get('blog/no-such-post-exists-at-all');
        $result->assertStatus(404);
    }

    // Category page ----------------------------------------------------------

    public function testExistingCategoryReturns200(): void
    {
        $result = $this->get('category/news');
        $result->assertStatus(200);
    }

    public function testMissingCategoryReturns404(): void
    {
        $result = $this->get('category/does-not-exist');
        $result->assertStatus(404);
    }

    // Tag page ---------------------------------------------------------------

    public function testExistingTagReturns200(): void
    {
        $result = $this->get('tag/php');
        $result->assertStatus(200);
    }

    public function testMissingTagReturns404(): void
    {
        $result = $this->get('tag/does-not-exist');
        $result->assertStatus(404);
    }

    // Archive ----------------------------------------------------------------

    public function testArchiveReturns200(): void
    {
        $result = $this->get('archive/2024/1');
        $result->assertStatus(200);
    }
}
