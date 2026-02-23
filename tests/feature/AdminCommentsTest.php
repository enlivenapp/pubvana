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
final class AdminCommentsTest extends CIUnitTestCase
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

    public function testCommentsIndexRequiresAuth(): void
    {
        $result = $this->get('admin/comments');
        $result->assertRedirect();
    }

    // Index ------------------------------------------------------------------

    public function testCommentsIndexReturns200ForAdmin(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/comments');
        $result->assertStatus(200);
    }

    public function testCommentsIndexWithApprovedFilterReturns200(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/comments?status=approved');
        $result->assertStatus(200);
    }

    // Approve ----------------------------------------------------------------

    public function testApproveCommentRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/comments/3/approve');
        $result->assertRedirect();
    }

    // Spam -------------------------------------------------------------------

    public function testSpamCommentRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/comments/1/spam');
        $result->assertRedirect();
    }

    // Delete -----------------------------------------------------------------

    public function testDeleteCommentRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/comments/1/delete');
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
