<?php

use App\Models\CategoryModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class CategoryModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private CategoryModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new CategoryModel();
    }

    // findBySlug() -----------------------------------------------------------

    public function testFindBySlugReturnsCategory(): void
    {
        $cat = $this->model->findBySlug('news');
        $this->assertNotNull($cat);
        $this->assertSame('news', $cat->slug);
    }

    public function testFindBySlugReturnsNullForMissing(): void
    {
        $cat = $this->model->findBySlug('does-not-exist');
        $this->assertNull($cat);
    }

    // getWithPostCount() -----------------------------------------------------

    public function testGetWithPostCountReturnsArray(): void
    {
        $results = $this->model->getWithPostCount();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testGetWithPostCountItemsAreObjects(): void
    {
        $results = $this->model->getWithPostCount();
        foreach ($results as $row) {
            $this->assertIsObject($row);
        }
    }

    public function testGetWithPostCountHasPostCountKey(): void
    {
        $results = $this->model->getWithPostCount();
        foreach ($results as $row) {
            $this->assertObjectHasProperty('post_count', $row);
            $this->assertGreaterThanOrEqual(0, (int) $row->post_count);
        }
    }

    public function testGetWithPostCountHasSlug(): void
    {
        $results = $this->model->getWithPostCount();
        $slugs   = array_map(fn($r) => $r->slug, $results);
        $this->assertContains('news', $slugs);
        $this->assertContains('tutorials', $slugs);
    }

    // CRUD -------------------------------------------------------------------

    public function testInsertAndFindCategory(): void
    {
        $id = $this->model->insert([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
        $this->assertIsInt($id);

        $cat = $this->model->find($id);
        $this->assertNotNull($cat);
        $this->assertSame('test-category', $cat->slug);
    }

    public function testDeleteCategory(): void
    {
        $this->model->delete(1);
        $cat = $this->model->find(1);
        $this->assertNull($cat);
    }
}
