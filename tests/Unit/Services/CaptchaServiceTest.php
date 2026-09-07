<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * CaptchaService against an in-memory database.
 *
 * Covers provider resolution and whitelisting, enablement, the per-area
 * switch list (registration-filtered storage), the widget snippet, and
 * the fail-closed verification rules. The network call is stubbed through
 * a subclass overriding postToSiteverify().
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(CaptchaService::class)]
final class CaptchaServiceTest extends TestCase
{
    /** @var string Last response token a stubbed postToSiteverify received */
    public string $seenToken = '';

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $this->seenToken = '';
    }

    // -----------------------------------------------------------------
    // Provider and enablement
    // -----------------------------------------------------------------

    public function testProviderDefaultsToNoneAndRejectsUnknownValues(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        self::assertSame('none', $service->provider());
        self::assertSame('', $service->postField());
        self::assertFalse($service->isEnabled());

        $app->settings()->set('Captcha.provider', 'turnstile');
        self::assertSame('none', $service->provider(), 'unknown providers must fall back to none');
    }

    public function testEnabledRequiresProviderAndSiteKey(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        $app->settings()->set('Captcha.provider', 'hcaptcha');
        self::assertFalse($service->isEnabled(), 'provider without a site key is not usable');

        $app->settings()->set('Captcha.site_key', 'site-1');
        self::assertTrue($service->isEnabled());
        self::assertSame('h-captcha-response', $service->postField());
    }

    public function testPostFieldMapsEachProvider(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        $app->settings()->set('Captcha.provider', 'recaptcha');
        self::assertSame('g-recaptcha-response', $service->postField());
    }

    // -----------------------------------------------------------------
    // Snippet
    // -----------------------------------------------------------------

    public function testSnippetIsEmptyUntilConfiguredAndEnforced(): void
    {
        $app = $this->buildApp();
        $app->adext()->register('captcha.area', 'default', 'test.comments', ['label' => 'Comments']);
        $service = new CaptchaService($app);

        self::assertSame('', $service->snippet(), 'nothing configured, nothing rendered');
        self::assertSame('', $service->snippetFor('test.comments'));

        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        self::assertSame('', $service->snippetFor('test.comments'), 'configured but the area switch is off');

        $service->setProtectedAreas(['test.comments' => '1']);
        $snippet = $service->snippetFor('test.comments');
        self::assertStringContainsString('class="h-captcha"', $snippet);
        self::assertStringContainsString('data-sitekey="site-1"', $snippet);
        self::assertStringContainsString('https://js.hcaptcha.com/1/api.js', $snippet);
        self::assertSame('', $service->snippetFor('test.other'), 'other areas stay empty');
    }

    public function testSnippetEscapesTheSiteKey(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        $app->settings()->set('Captcha.provider', 'recaptcha');
        $app->settings()->set('Captcha.site_key', 'key" onload="alert(1)');

        $snippet = $service->snippet();
        self::assertStringNotContainsString('onload="alert(1)" data', $snippet);
        self::assertStringContainsString('data-sitekey="key&quot; onload=&quot;alert(1)"', $snippet);
    }

    // -----------------------------------------------------------------
    // Protected areas
    // -----------------------------------------------------------------

    public function testProtectedAreasAreStoredFilteredAndReadBack(): void
    {
        $app = $this->buildApp();
        $app->adext()->register('captcha.area', 'default', 'test.comments', ['label' => 'Comments']);
        $app->adext()->register('captcha.area', 'default', 'test.forms', ['label' => 'Forms']);
        $service = new CaptchaService($app);

        self::assertSame(['test.comments', 'test.forms'], array_keys($service->areas()));
        self::assertFalse($service->isProtected('test.comments'));

        // An unknown key must never enter the stored list.
        $service->setProtectedAreas(['test.comments' => '1', 'test.nope' => '1']);
        self::assertTrue($service->isProtected('test.comments'));
        self::assertFalse($service->isProtected('test.nope'));

        // Turning the only switch off stores an empty list.
        $service->setProtectedAreas([]);
        self::assertFalse($service->isProtected('test.comments'));
    }

    public function testEnforcedForNeedsConfigAndSwitch(): void
    {
        $app = $this->buildApp();
        $app->adext()->register('captcha.area', 'default', 'test.comments', ['label' => 'Comments']);
        $service = new CaptchaService($app);

        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.protected', json_encode(['test.comments']));
        self::assertTrue($service->enforcedFor('test.comments'));

        $app->settings()->set('Captcha.site_key', '');
        self::assertFalse($service->enforcedFor('test.comments'), 'a missing site key never enforces');
    }

    // -----------------------------------------------------------------
    // Verification
    // -----------------------------------------------------------------

    public function testVerifyPassesWhenDisabledAndFailsClosedWithoutSecret(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        self::assertTrue($service->verify('token', '127.0.0.1'), 'no provider configured, nothing to check');

        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        self::assertFalse($service->verify('token', '127.0.0.1'), 'a missing secret is a misconfiguration, not a hall-pass');
    }

    public function testVerifyRejectsAnEmptyTokenWithoutNetwork(): void
    {
        $app = $this->buildApp();
        $service = new CaptchaService($app);

        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');

        self::assertFalse($service->verify('', '127.0.0.1'));
    }

    public function testVerifyReadsTheProviderVerdict(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'recaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');

        $service = new class($app) extends CaptchaService {
            public ?array $posted = null;
            public string $endpoint = '';

            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                $this->endpoint = $endpoint;
                $this->posted = $payload;
                return ['success' => true];
            }
        };
        self::assertTrue($service->verify('token-1', '127.0.0.1'));
        self::assertSame('https://www.google.com/recaptcha/api/siteverify', $service->endpoint);
        self::assertSame('token-1', $service->posted['response'] ?? null);
        self::assertSame('secret-1', $service->posted['secret'] ?? null);

        $failing = new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return ['success' => false];
            }
        };
        self::assertFalse($failing->verify('token-1', '127.0.0.1'));

        $garbage = new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return null;
            }
        };
        self::assertFalse($garbage->verify('token-1', '127.0.0.1'), 'an unparseable answer must fail closed');
    }

    public function testVerifySubmissionReadsTheProviderField(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');

        $test = $this;
        $service = new class($app, $test) extends CaptchaService {
            public function __construct(Engine $app, private CaptchaServiceTest $test)
            {
                parent::__construct($app);
            }

            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                $this->test->seenToken = (string) ($payload['response'] ?? '');
                return ['success' => true];
            }
        };

        self::assertTrue($service->verifySubmission(['h-captcha-response' => 'tok'], '127.0.0.1'));
        self::assertSame('tok', $this->seenToken);

        self::assertFalse($service->verifySubmission([], '127.0.0.1'), 'a missing token fails');
        self::assertFalse($service->verifySubmission(['h-captcha-response' => ['x']], '127.0.0.1'), 'a non-string token fails');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Fresh engine wired with the real settings store and extension
     * registry over the shared in-memory database. The engine must also
     * be the global one so any \Flight::app() call inside a service
     * resolves to this test's engine.
     */
    private function buildApp(): Engine
    {
        $app = $this->app([
            'db'       => fn(): \PDO => Sqlite::connection(),
            'settings' => $this->singleton(fn(): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
        ]);
        \Flight::setEngine($app);

        return $app;
    }

    /**
     * Wrap a lazy provider in a per-engine singleton guard: Flight
     * resolves mapped services through repeated calls, so a bare
     * closure would hand back a fresh instance every time.
     *
     * @param callable $provider Zero-argument factory
     * @return callable Singleton-guarded factory
     */
    private function singleton(callable $provider): callable
    {
        return function () use ($provider) {
            static $instance = null;
            if ($instance === null) {
                $instance = $provider();
            }
            return $instance;
        };
    }
}
