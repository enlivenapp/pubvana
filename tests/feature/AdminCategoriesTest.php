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
final class AdminCategoriesTest extends CIUnitTestCase
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

    public function testCategoriesIndexRequiresAuth(): void
    {
        $result = $this->get('admin/categories');
        $result->assertRedirect();
    }

    // Index ------------------------------------------------------------------

    public function testCategoriesIndexReturns200ForAdmin(): void
    {
        $result = $this->actingAs($this->getAdminUser())->get('admin/categories');
        $result->assertStatus(200);
    }

    // Store ------------------------------------------------------------------

    public function testCategoriesStoreValidRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/categories/create', [
            'name'        => 'New Category',
            'description' => 'A test category.',
        ]);
        $result->assertRedirect();
    }

    public function testCategoriesStoreMissingNameRedirectsBack(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/categories/create', [
            'description' => 'No name here.',
        ]);
        $result->assertRedirect();
    }

    // Delete -----------------------------------------------------------------

    public function testCategoriesDeleteRedirects(): void
    {
        $result = $this->actingAs($this->getAdminUser())->post('admin/categories/1/delete');
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
