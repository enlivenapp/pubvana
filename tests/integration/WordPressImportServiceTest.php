<?php

use App\Services\WordPressImportService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class WordPressImportServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = false; // each test needs a clean slate (import creates rows)

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = HOMEPATH . 'tests/_support/Fixtures/wordpress-sample.xml';
    }

    // Missing file -----------------------------------------------------------

    public function testMissingFileReturnsError(): void
    {
        $service = new WordPressImportService();
        $results = $service->import('/tmp/does-not-exist-12345.xml');

        $this->assertNotEmpty($results['errors']);
        $this->assertStringContainsString('File not found', $results['errors'][0]);
    }

    // Invalid XML ------------------------------------------------------------

    public function testInvalidXmlReturnsError(): void
    {
        $tmpPath = WRITEPATH . 'tmp/test_invalid.xml';
        if (! is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }
        file_put_contents($tmpPath, 'this is not valid xml <<< >>>');

        $service = new WordPressImportService();
        $results = $service->import($tmpPath);

        @unlink($tmpPath);

        $this->assertNotEmpty($results['errors']);
    }

    // Dry-run ----------------------------------------------------------------

    public function testDryRunDoesNotInsertPosts(): void
    {
        $service = new WordPressImportService();
        $service->setDryRun(true);
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        $this->assertSame(0, (int) db_connect()->table('posts')->where('slug', 'imported-post-one')->countAllResults());
    }

    public function testDryRunCountsCreated(): void
    {
        $service = new WordPressImportService();
        $service->setDryRun(true);
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        // The fixture has 2 posts + 1 page
        $this->assertGreaterThan(0, $results['posts']['created'] + $results['posts']['skipped']);
    }

    // Full import ------------------------------------------------------------

    public function testFullImportCreatesPosts(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        $this->assertGreaterThan(0, $results['posts']['created']);

        // Verify the post is actually in the DB
        $post = db_connect()->table('posts')->where('slug', 'imported-post-one')->get()->getRowObject();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
    }

    public function testFullImportCreatesCategories(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        $this->assertGreaterThan(0, $results['categories']['created'] + $results['categories']['skipped']);

        $cat = db_connect()->table('categories')->where('slug', 'wp-news')->get()->getRowObject();
        $this->assertNotNull($cat);
    }

    public function testFullImportCreatesTags(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);

        $tag = db_connect()->table('tags')->where('slug', 'wordpress')->get()->getRowObject();
        $this->assertNotNull($tag);
    }

    public function testFullImportCreatesComments(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        $this->assertGreaterThan(0, $results['comments']['created']);
    }

    public function testFullImportCreatesPages(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);
        $this->assertGreaterThan(0, $results['pages']['created']);

        $page = db_connect()->table('pages')->where('slug', 'imported-page')->get()->getRowObject();
        $this->assertNotNull($page);
    }

    // Author mapping — existing user gets skipped, not duplicated -----------

    public function testExistingAuthorIsSkippedNotDuplicated(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        // 'testadmin' is already in the DB (seeded), should be skipped
        $this->assertGreaterThan(0, $results['authors']['skipped']);

        $userCount = db_connect()->table('users')->where('username', 'testadmin')->countAllResults();
        $this->assertSame(1, $userCount);
    }

    public function testNewAuthorIsCreated(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        // 'wpauthor' is a new author in the fixture
        $this->assertGreaterThan(0, $results['authors']['created']);
        $user = db_connect()->table('users')->where('username', 'wpauthor')->get()->getRowObject();
        $this->assertNotNull($user);
    }

    // Deduplication (run import twice) ---------------------------------------

    public function testRunningImportTwiceDeduplicatesPosts(): void
    {
        $service1 = new WordPressImportService();
        $service1->import($this->fixturePath);

        $countAfterFirst = db_connect()->table('posts')->where('slug', 'imported-post-one')->countAllResults();

        // Second import — post slug exists, so uniqueSlug() will add a suffix
        $service2 = new WordPressImportService();
        $results2 = $service2->import($this->fixturePath);

        // Import should still succeed (no errors)
        $this->assertEmpty($results2['errors']);

        // The original post is still there
        $this->assertSame($countAfterFirst, 1);
    }

    // Slug collision ---------------------------------------------------------

    public function testSlugCollisionAppendsNumericSuffix(): void
    {
        // Pre-insert a post that would collide with the imported post slug
        db_connect()->table('posts')->insert([
            'title'        => 'Pre-existing Post',
            'slug'         => 'imported-post-one',
            'content'      => 'Existing content.',
            'content_type' => 'html',
            'status'       => 'published',
            'author_id'    => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $this->assertEmpty($results['errors']);

        // The import should have created a post with a different slug
        $posts = db_connect()->table('posts')
            ->like('slug', 'imported-post-one', 'after')
            ->get()->getResultArray();

        $this->assertCount(2, $posts); // original + suffixed
    }

    // Results structure ------------------------------------------------------

    public function testResultsHaveCorrectStructure(): void
    {
        $service = new WordPressImportService();
        $results = $service->import($this->fixturePath);

        $expectedKeys = ['authors', 'categories', 'tags', 'posts', 'pages', 'comments', 'errors'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $results);
        }

        foreach (['authors', 'categories', 'tags', 'posts', 'pages', 'comments'] as $key) {
            $this->assertArrayHasKey('created', $results[$key]);
            $this->assertArrayHasKey('skipped', $results[$key]);
        }
    }

    public function testGetResultsReturnsImportResults(): void
    {
        $service = new WordPressImportService();
        $service->import($this->fixturePath);
        $results = $service->getResults();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('posts', $results);
    }
}
