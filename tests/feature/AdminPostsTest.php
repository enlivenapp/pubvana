<?php

use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class AdminPostsTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    // Reset auth state so sessions from a previous test class do not bleed in.
    protected function setUp(): void
    {
        parent::setUp();
        auth('session')->logout();
    }

    // Auth guard -------------------------------------------------------------

    public function testPostsIndexRequiresAuth(): void
    {
        $result = $this->get('admin/posts');
        $result->assertRedirect();
    }

    // Index ------------------------------------------------------------------

    public function testPostsIndexReturns200ForAdmin(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/posts');
        $result->assertStatus(200);
    }

    public function testPostsIndexWithStatusFilterReturns200(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/posts?status=published');
        $result->assertStatus(200);
    }

    // Create -----------------------------------------------------------------

    public function testPostsCreatePageReturns200(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/posts/create');
        $result->assertStatus(200);
    }

    // Store ------------------------------------------------------------------

    public function testPostsStoreValidCreatesPost(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/posts/create', [
            'title'   => 'My New Test Post',
            'content' => 'Content of the new post.',
            'status'  => 'draft',
        ]);
        $result->assertRedirect();
    }

    public function testPostsStoreMissingTitleRedirectsBack(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/posts/create', [
            'content' => 'No title here.',
            'status'  => 'draft',
        ]);
        $result->assertRedirect();
    }

    public function testPostsStoreInvalidStatusRedirectsBack(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/posts/create', [
            'title'  => 'Title',
            'status' => 'invalid-status',
        ]);
        $result->assertRedirect();
    }

    // Edit -------------------------------------------------------------------

    public function testPostsEditPageReturns200(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/posts/1/edit');
        $result->assertStatus(200);
    }

    public function testPostsEditMissingReturns404(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/posts/9999/edit');
        $result->assertStatus(404);
    }

    // Update -----------------------------------------------------------------

    public function testPostsUpdateValidRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/posts/1/edit', [
            'title'   => 'Updated Title',
            'content' => 'Updated content.',
            'status'  => 'published',
        ]);
        $result->assertRedirect();
    }

    // Delete -----------------------------------------------------------------

    public function testPostsDeleteRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/posts/1/delete');
        $result->assertRedirect();
    }

    // Helper -----------------------------------------------------------------

    private function getAdminUser(): \CodeIgniter\Shield\Entities\User
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        return $userModel->withIdentities()->withGroups()->find(1);
    }
}
