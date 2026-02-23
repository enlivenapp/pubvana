<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class SearchTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    public function testSearchWithoutQueryReturns200(): void
    {
        $result = $this->get('search');
        $result->assertStatus(200);
    }

    public function testSearchWithQueryReturns200(): void
    {
        $result = $this->get('search?q=hello');
        $result->assertStatus(200);
    }

    public function testSearchWithEmptyQueryReturns200(): void
    {
        $result = $this->get('search?q=');
        $result->assertStatus(200);
    }

    public function testSearchWithMatchingTermReturns200(): void
    {
        $result = $this->get('search?q=Hello+World');
        $result->assertStatus(200);
    }

    public function testSearchWithNoMatchesReturns200(): void
    {
        $result = $this->get('search?q=xyzzy-no-match-at-all');
        $result->assertStatus(200);
    }
}
