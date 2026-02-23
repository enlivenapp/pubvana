<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class PagesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    public function testPublishedPageReturns200(): void
    {
        $result = $this->get('about');
        $result->assertStatus(200);
    }

    public function testDraftPageReturns404(): void
    {
        $result = $this->get('secret-page');
        $result->assertStatus(404);
    }

    public function testNonExistentPageReturns404(): void
    {
        $result = $this->get('page-that-does-not-exist');
        $result->assertStatus(404);
    }
}
