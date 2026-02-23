<?php

use App\Models\PostModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class PostModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private PostModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new PostModel();
    }

    // published() scope ------------------------------------------------------

    public function testPublishedReturnsonlyPublishedPosts(): void
    {
        $posts = $this->model->published()->findAll();
        $this->assertNotEmpty($posts);
        foreach ($posts as $post) {
            $this->assertSame('published', $post->status);
        }
    }

    public function testPublishedExcludesDraftPosts(): void
    {
        $posts = $this->model->published()->findAll();
        $slugs = array_map(fn($p) => $p->slug, $posts);
        $this->assertNotContains('a-draft-post', $slugs);
    }

    public function testPublishedIncludesSeededPublishedPosts(): void
    {
        $posts = $this->model->published()->findAll();
        $slugs = array_map(fn($p) => $p->slug, $posts);
        $this->assertContains('hello-world', $slugs);
    }

    // findBySlug() -----------------------------------------------------------

    public function testFindBySlugReturnsCorrectPost(): void
    {
        $post = $this->model->findBySlug('hello-world');
        $this->assertNotNull($post);
        $this->assertSame('hello-world', $post->slug);
    }

    public function testFindBySlugReturnsNullForMissingSlug(): void
    {
        $post = $this->model->findBySlug('does-not-exist');
        $this->assertNull($post);
    }

    public function testFindBySlugReturnsDraftPost(): void
    {
        $post = $this->model->findBySlug('a-draft-post');
        $this->assertNotNull($post);
        $this->assertSame('draft', $post->status);
    }

    // incrementViews() -------------------------------------------------------

    public function testIncrementViewsIncreasesViewCount(): void
    {
        $before = $this->model->find(1);
        $this->model->incrementViews(1);
        $after = $this->model->find(1);
        $this->assertSame((int) $before->views + 1, (int) $after->views);
    }

    public function testIncrementViewsMultipleTimes(): void
    {
        $before = (int) $this->model->find(1)->views;
        $this->model->incrementViews(1);
        $this->model->incrementViews(1);
        $after = (int) $this->model->find(1)->views;
        $this->assertSame($before + 2, $after);
    }

    // byCategory() -----------------------------------------------------------

    public function testByCategoryReturnsPostsInCategory(): void
    {
        $posts = $this->model->published()->byCategory(1)->findAll();
        $this->assertNotEmpty($posts);
        $slugs = array_map(fn($p) => $p->slug, $posts);
        $this->assertContains('cat-post', $slugs);
    }

    public function testByCategoryEmptyForUnusedCategory(): void
    {
        $posts = $this->model->published()->byCategory(999)->findAll();
        $this->assertEmpty($posts);
    }

    // byTag() ----------------------------------------------------------------

    public function testByTagReturnsPostsWithTag(): void
    {
        $posts = $this->model->published()->byTag(1)->findAll();
        $this->assertNotEmpty($posts);
        $slugs = array_map(fn($p) => $p->slug, $posts);
        $this->assertContains('tag-post', $slugs);
    }

    public function testByTagEmptyForUnusedTag(): void
    {
        $posts = $this->model->published()->byTag(999)->findAll();
        $this->assertEmpty($posts);
    }

    // soft delete ------------------------------------------------------------

    public function testSoftDeleteHidesPost(): void
    {
        $this->model->delete(1);
        $post = $this->model->find(1);
        $this->assertNull($post);
    }

    public function testSoftDeletedPostFoundWithDeleted(): void
    {
        $this->model->delete(1);
        $post = $this->model->withDeleted()->find(1);
        $this->assertNotNull($post);
    }

    // featured() scope -------------------------------------------------------

    public function testFeaturedReturnsFeaturedPosts(): void
    {
        $posts = $this->model->featured()->findAll();
        $this->assertNotEmpty($posts);
        foreach ($posts as $post) {
            $this->assertSame('1', (string) $post->is_featured);
        }
    }
}
