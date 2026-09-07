<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Forms;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Plugins\Forms\Services\FormsService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * FormsService captcha enforcement against an in-memory database.
 *
 * Covers the submitForm() rules: pass-through when the forms area is not
 * protected, rejection with the captcha error when the token is missing
 * or the (stubbed) provider says no, and the captcha field never reaching
 * the stored payload.
 *
 * @package Pubvana\Tests\Unit\Plugins\Forms
 */
#[CoversClass(FormsService::class)]
final class FormsServiceCaptchaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testSubmitStoresWhenCaptchaNotEnforced(): void
    {
        $app = $this->buildApp();
        $service = $this->service($app);
        $form = $this->publishedForm($service);

        $result = $service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '127.0.0.1']);

        self::assertTrue($result['ok']);
        self::assertSame(1, $this->submissionCount());
    }

    public function testSubmitRejectsAMissingTokenWhenEnforced(): void
    {
        $app = $this->buildApp();
        $this->enforceFormsArea($app, $this->alwaysAcceptingCaptcha($app));
        $service = $this->service($app);
        $form = $this->publishedForm($service);

        $result = $service->submitForm($form, ['name' => 'Ada'], ['ip_address' => '127.0.0.1']);

        self::assertFalse($result['ok']);
        self::assertContains('Captcha verification failed. Please try again.', $result['errors']);
        self::assertSame(0, $this->submissionCount(), 'a rejected captcha must never store a submission');
    }

    public function testSubmitRejectsWhenTheProviderRejects(): void
    {
        $app = $this->buildApp();
        $this->enforceFormsArea($app, $this->alwaysRejectingCaptcha($app));
        $service = $this->service($app);
        $form = $this->publishedForm($service);

        $result = $service->submitForm($form, [
            'name'               => 'Ada',
            'h-captcha-response' => 'tok',
        ], ['ip_address' => '127.0.0.1']);

        self::assertFalse($result['ok']);
        self::assertSame(0, $this->submissionCount());
    }

    public function testSubmitRemovesTheTokenFromTheStoredPayload(): void
    {
        $app = $this->buildApp();
        $this->enforceFormsArea($app, $this->alwaysAcceptingCaptcha($app));
        $service = $this->service($app);
        $form = $this->publishedForm($service);

        $result = $service->submitForm($form, [
            'name'               => 'Ada',
            'h-captcha-response' => 'tok',
        ], ['ip_address' => '127.0.0.1']);

        self::assertTrue($result['ok']);
        $payload = $service->decodeSubmissionPayload($this->lastPayloadJson());
        self::assertArrayHasKey('name', $payload);
        self::assertArrayNotHasKey('h-captcha-response', $payload, 'the captcha token is not visitor data');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Fresh engine wired with the real settings store, extension registry,
     * and captcha service over the shared in-memory database. The engine
     * must also be the global one so \Flight::app() calls inside services
     * resolve to this test's engine.
     */
    private function buildApp(): Engine
    {
        $app = $this->app([
            'db'       => fn(): PDO => $this->pdo,
            'settings' => $this->singleton(fn(): SettingsService => new SettingsService(\Flight::app())),
            'adext'    => $this->singleton(fn(): ExtensionRegistry => new ExtensionRegistry()),
            'captcha'  => $this->singleton(fn(): CaptchaService => new CaptchaService(\Flight::app())),
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

    /**
     * Point the Captcha.provider/Site/secret/protected settings at the
     * forms area so enforcedFor('forms') is true, and swap in a stubbed
     * provider so no network call ever happens.
     */
    private function enforceFormsArea(Engine $app, CaptchaService $stub): void
    {
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['forms']));
        $app->map('captcha', fn(): CaptchaService => $stub);
    }

    /**
     * FormsService with the session rate limit disabled so tests never
     * depend on session state.
     */
    private function service(Engine $app): FormsService
    {
        return new FormsService($this->pdo, $app, [
            'route_prefix'       => '/forms',
            'per_page'           => 25,
            'submissions_per_page' => 25,
            'rate_limit_seconds' => 0,
        ]);
    }

    /**
     * A published form with one required text field.
     */
    private function publishedForm(FormsService $service): object
    {
        return $service->createForm([
            'name'              => 'Contact',
            'slug'              => 'contact',
            'status'            => 'published',
            'submit_label'      => 'Send',
            'success_message'   => 'Thanks',
            'notification_emails' => '',
            'field_definitions' => json_encode([
                [
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => 'Name',
                    'required' => true,
                    'width'    => 'full',
                    'options'  => [],
                ],
            ]),
        ]);
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

    private function submissionCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM form_submissions');

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    private function lastPayloadJson(): ?string
    {
        $stmt = $this->pdo->query('SELECT payload_json FROM form_submissions ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? ($row['payload_json'] ?? null) : null;
    }
}
