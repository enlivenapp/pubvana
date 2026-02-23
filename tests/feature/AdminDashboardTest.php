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
final class AdminDashboardTest extends CIUnitTestCase
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

    // Unauthenticated --------------------------------------------------------

    public function testUnauthenticatedAdminRedirectsToLogin(): void
    {
        $result = $this->get('admin');
        // AdminFilter redirects to /login
        $result->assertRedirect();
        $result->assertStatus(302);
    }

    // Authenticated ----------------------------------------------------------

    public function testAuthenticatedAdminCanAccessDashboard(): void
    {
        $user   = $this->getAdminUser();
        $result = $this->actingAs($user)->get('admin');
        $result->assertStatus(200);
    }

    // Helper -----------------------------------------------------------------

    private function getAdminUser(): \CodeIgniter\Shield\Entities\User
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        return $userModel->withIdentities()->withGroups()->find(1);
    }
}
