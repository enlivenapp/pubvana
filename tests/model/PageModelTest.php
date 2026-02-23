<?php

use App\Models\PageModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class PageModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private PageModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PageModel();
    }

    // findBySlug() -----------------------------------------------------------

    public function testFindBySlugReturnsPublishedPage(): void
    {
        $page = $this->model->findBySlug('about');
        $this->assertNotNull($page);
        $this->assertSame('about', $page->slug);
    }

    public function testFindBySlugReturnsDraftPage(): void
    {
        $page = $this->model->findBySlug('secret-page');
        $this->assertNotNull($page);
        $this->assertSame('draft', $page->status);
    }

    public function testFindBySlugReturnsNullForMissing(): void
    {
        $page = $this->model->findBySlug('no-such-page');
        $this->assertNull($page);
    }

    // published() scope ------------------------------------------------------

    public function testPublishedScopeExcludesDrafts(): void
    {
        $pages = $this->model->published()->findAll();
        foreach ($pages as $page) {
            $this->assertSame('published', $page->status);
        }
    }

    public function testPublishedScopeIncludesAboutPage(): void
    {
        $pages = $this->model->published()->findAll();
        $slugs = array_map(fn($p) => $p->slug, $pages);
        $this->assertContains('about', $slugs);
    }

    public function testPublishedScopeExcludesSecretPage(): void
    {
        $pages = $this->model->published()->findAll();
        $slugs = array_map(fn($p) => $p->slug, $pages);
        $this->assertNotContains('secret-page', $slugs);
    }

    // CRUD -------------------------------------------------------------------

    public function testInsertPage(): void
    {
        $id = $this->model->insert([
            'title'   => 'Contact',
            'slug'    => 'contact',
            'content' => '<p>Contact us.</p>',
            'status'  => 'published',
        ]);
        $this->assertIsInt($id);

        $page = $this->model->find($id);
        $this->assertNotNull($page);
        $this->assertSame('contact', $page->slug);
    }
}
