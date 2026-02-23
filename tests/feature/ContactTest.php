<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\Seeds\PubvanaSeeder;

/**
 * @internal
 */
final class ContactTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = false;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace    = ['App', 'CodeIgniter\\Shield'];
    protected $seed        = PubvanaSeeder::class;
    protected $seedOnce    = true;

    // GET --------------------------------------------------------------------

    public function testContactGetReturns200(): void
    {
        $result = $this->get('contact');
        $result->assertStatus(200);
    }

    // POST valid -------------------------------------------------------------

    public function testContactPostValidRedirects(): void
    {
        // HCaptchaService::verify() returns true in ENVIRONMENT=testing
        $result = $this->post('contact', [
            'name'    => 'Test User',
            'email'   => 'test@example.com',
            'message' => 'This is a valid test message from the test suite.',
        ]);

        // Should redirect to /contact with a success flash
        $result->assertRedirect();
    }

    // POST invalid -----------------------------------------------------------

    public function testContactPostMissingNameRedirectsBack(): void
    {
        $result = $this->post('contact', [
            'email'   => 'test@example.com',
            'message' => 'A message without a name.',
        ]);

        $result->assertRedirect();
    }

    public function testContactPostInvalidEmailRedirectsBack(): void
    {
        $result = $this->post('contact', [
            'name'    => 'Tester',
            'email'   => 'not-an-email',
            'message' => 'Message with invalid email address here.',
        ]);

        $result->assertRedirect();
    }

    public function testContactPostShortMessageRedirectsBack(): void
    {
        $result = $this->post('contact', [
            'name'    => 'Tester',
            'email'   => 'test@example.com',
            'message' => 'Short',
        ]);

        $result->assertRedirect();
    }
}
