<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use flight\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaMiddleware;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * CaptchaMiddleware decision logic.
 *
 * The reject() side effect (render + exit) is stubbed out and recorded;
 * everything else is the real service path: POST-only, exact sign-in path
 * match, the enforcedFor('login') switch, and the verify() verdict.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(CaptchaMiddleware::class)]
final class CaptchaMiddlewareTest extends TestCase
{
    /** @var int How many times reject() fired */
    public int $rejected = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Sqlite::recreate();
        $this->rejected = 0;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/';
        $_POST = [];
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
        $_POST = [];
        parent::tearDown();
    }

    public function testIgnoresNonPostRequests(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/auth/login']);
        $this->protectLoginArea($app);

        $this->middleware($app)->before();
        self::assertSame(0, $this->rejected);
    }

    public function testIgnoresOtherPaths(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/comments/blog/1']);
        $this->protectLoginArea($app);

        $this->middleware($app)->before();
        self::assertSame(0, $this->rejected);
    }

    public function testIgnoresLoginWhenTheAreaSwitchIsOff(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/auth/login']);
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        // No Captcha.protected entry: the switch is off.

        $this->middleware($app)->before();
        self::assertSame(0, $this->rejected);
    }

    public function testRejectsLoginFailingClosedOnMissingSecret(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/auth/login?next=/admin']);
        $this->protectLoginArea($app);
        // Site key present, secret key missing: verification must fail closed.

        $this->middleware($app)->before();
        self::assertSame(1, $this->rejected);
    }

    public function testPassesLoginWhenTheProviderAccepts(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/auth/login']);
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['login']));
        $app->map('captcha', fn(): CaptchaService => $this->alwaysAcceptingCaptcha($app));
        $_POST['h-captcha-response'] = 'tok';

        $this->middleware($app)->before();
        self::assertSame(0, $this->rejected);
    }

    public function testRejectsLoginWhenTheProviderRefuses(): void
    {
        $app = $this->buildApp(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/auth/login']);
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['login']));
        $app->map('captcha', fn(): CaptchaService => $this->alwaysRejectingCaptcha($app));
        $_POST['h-captcha-response'] = 'tok';

        $this->middleware($app)->before();
        self::assertSame(1, $this->rejected);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Fresh engine wired with the real settings store, extension registry,
     * and captcha service over the shared in-memory database, plus the
     * Shield config the middleware reads the sign-in path from. The engine
     * must also be the global one so \Flight::app() calls inside services
     * resolve to this test's engine.
     *
     * @param array{REQUEST_METHOD?: string, REQUEST_URI?: string} $server
     */
    private function buildApp(array $server = []): Engine
    {
        foreach ($server as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $app = $this->app([
            'db'       => fn(): \PDO => Sqlite::connection(),
            'settings' => $this->singleton(fn(): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
            'captcha'  => $this->singleton(fn(): CaptchaService => new CaptchaService(\Flight::app())),
        ]);
        \Flight::setEngine($app);
        $app->set('enlivenapp.flight-shield', [
            'redirects' => ['login' => '/auth/login'],
        ]);

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

    /**
     * Configure a provider and turn the login switch on (the secret key is
     * intentionally left unset so verification fails closed on its own).
     */
    private function protectLoginArea(Engine $app): void
    {
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.protected', json_encode(['login']));
    }

    /**
     * Middleware with the render-and-exit side effect recorded instead of
     * executed.
     */
    private function middleware(Engine $app): CaptchaMiddleware
    {
        $test = $this;

        return new class($app, $test) extends CaptchaMiddleware {
            public function __construct(Engine $app, private CaptchaMiddlewareTest $test)
            {
                parent::__construct($app);
            }

            protected function reject(): void
            {
                $this->test->rejected++;
            }
        };
    }

    /**
     * CaptchaService with the provider call stubbed to always accept.
     */
    private function alwaysAcceptingCaptcha(Engine $app): CaptchaService
    {
        return new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return ['success' => true];
            }
        };
    }

    /**
     * CaptchaService with the provider call stubbed to always reject.
     */
    private function alwaysRejectingCaptcha(Engine $app): CaptchaService
    {
        return new class($app) extends CaptchaService {
            protected function postToSiteverify(string $endpoint, array $payload): ?array
            {
                return ['success' => false];
            }
        };
    }
}
