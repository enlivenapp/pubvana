<?php

use App\Models\TagModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class TagModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private TagModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TagModel();
    }

    // findBySlug() -----------------------------------------------------------

    public function testFindBySlugReturnsTag(): void
    {
        $tag = $this->model->findBySlug('php');
        $this->assertNotNull($tag);
        $this->assertSame('php', $tag->slug);
    }

    public function testFindBySlugReturnsNullForMissing(): void
    {
        $tag = $this->model->findBySlug('does-not-exist');
        $this->assertNull($tag);
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

    public function testGetWithPostCountHasExpectedSlugs(): void
    {
        $results = $this->model->getWithPostCount();
        $slugs   = array_map(fn($r) => $r->slug, $results);
        $this->assertContains('php', $slugs);
        $this->assertContains('codeigniter', $slugs);
    }

    // CRUD -------------------------------------------------------------------

    public function testInsertAndFindTag(): void
    {
        $id = $this->model->insert([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);
        $this->assertIsInt($id);

        $tag = $this->model->find($id);
        $this->assertNotNull($tag);
        $this->assertSame('laravel', $tag->slug);
    }

    public function testDeleteTag(): void
    {
        $this->model->delete(1);
        $tag = $this->model->find(1);
        $this->assertNull($tag);
    }
}
