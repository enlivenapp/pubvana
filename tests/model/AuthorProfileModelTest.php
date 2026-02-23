<?php

use App\Models\AuthorProfileModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class AuthorProfileModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    private AuthorProfileModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AuthorProfileModel();
    }

    // getByUserId() ----------------------------------------------------------

    public function testGetByUserIdReturnsNullWhenNoProfile(): void
    {
        // No profile is seeded for user 1
        $profile = $this->model->getByUserId(1);
        $this->assertNull($profile);
    }

    public function testGetByUserIdReturnsProfileAfterInsert(): void
    {
        $this->model->insert([
            'user_id'      => 1,
            'display_name' => 'Test Admin',
            'bio'          => 'Bio here.',
        ]);

        $profile = $this->model->getByUserId(1);
        $this->assertNotNull($profile);
        $this->assertSame('Test Admin', $profile->display_name);
    }

    // upsert() — create branch -----------------------------------------------

    public function testUpsertCreatesProfileWhenNoneExists(): void
    {
        $this->model->upsert(1, [
            'display_name' => 'New Name',
            'bio'          => 'A bio.',
        ]);

        $profile = $this->model->getByUserId(1);
        $this->assertNotNull($profile);
        $this->assertSame('New Name', $profile->display_name);
    }

    // upsert() — update branch -----------------------------------------------

    public function testUpsertUpdatesExistingProfile(): void
    {
        $this->model->upsert(1, ['display_name' => 'Initial Name']);

        $this->model->upsert(1, ['display_name' => 'Updated Name']);

        $profile = $this->model->getByUserId(1);
        $this->assertNotNull($profile);
        $this->assertSame('Updated Name', $profile->display_name);
    }

    public function testUpsertDoesNotDuplicateProfile(): void
    {
        $this->model->upsert(1, ['display_name' => 'Once']);
        $this->model->upsert(1, ['display_name' => 'Twice']);

        $count = $this->model->where('user_id', 1)->countAllResults();
        $this->assertSame(1, $count);
    }

    // partial field updates --------------------------------------------------

    public function testUpsertPreservesUnmentionedFields(): void
    {
        $this->model->upsert(1, ['display_name' => 'Jane', 'bio' => 'Original bio']);
        $this->model->upsert(1, ['display_name' => 'Jane Updated']);

        $profile = $this->model->getByUserId(1);
        // display_name updated, bio should survive the partial update
        $this->assertSame('Jane Updated', $profile->display_name);
    }
}
