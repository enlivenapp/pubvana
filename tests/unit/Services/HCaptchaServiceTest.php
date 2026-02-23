<?php

use App\Services\HCaptchaService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class HCaptchaServiceTest extends CIUnitTestCase
{
    public function testVerifyReturnsTrueInTestingEnvironment(): void
    {
        // ENVIRONMENT is 'testing' during PHPUnit runs, so verify() must
        // short-circuit and return true without making any HTTP call.
        $service = new HCaptchaService();
        $this->assertTrue($service->verify('any-token'));
    }

    public function testVerifyReturnsTrueForEmptyTokenInTestingEnvironment(): void
    {
        $service = new HCaptchaService();
        $this->assertTrue($service->verify(''));
    }
}
