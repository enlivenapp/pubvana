<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Plugins\Comments;

use flight\Engine;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\CaptchaService;
use Pubvana\Services\ExtensionRegistry;
use Pubvana\Services\SettingsService;
use Pubvana\Plugins\Comments\Services\CommentService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * CommentService captcha enforcement against an in-memory database.
 *
 * Covers the create() rules: pass-through when the comment form area is
 * not protected, fail-closed on a missing secret, rejection of a missing
 * token, and a stored comment once the (stubbed) provider accepts.
 *
 * @package Pubvana\Tests\Unit\Plugins\Comments
 */
#[CoversClass(CommentService::class)]
final class CommentServiceCaptchaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
    }

    public function testCreateStoresCommentWhenCaptchaNotEnforced(): void
    {
        $app = $this->buildApp();
        $service = new CommentService($this->pdo, $app);

        $comment = $service->create([
            'commentable_type' => 'blog',
            'commentable_id'   => 1,
            'body'             => 'Hello world',
            'ip_address'       => '127.0.0.1',
            'guest_name'       => 'Ada',
        ]);

        self::assertSame(1, $this->commentCount());
        self::assertSame('pending', (string) $comment->status, 'default status comes from settings');
    }

    public function testCreateFailsClosedWhenProtectedAndSecretMissing(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.protected', json_encode(['comments']));
        $service = new CommentService($this->pdo, $app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Captcha verification failed.');

        $service->create([
            'commentable_type' => 'blog',
            'commentable_id'   => 1,
            'body'             => 'Hello world',
            'ip_address'       => '127.0.0.1',
            'captcha_token'    => 'tok',
        ]);
        self::assertSame(0, $this->commentCount(), 'nothing may be stored on a failed check');
    }

    public function testCreateRejectsAMissingTokenWhenEnforced(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['comments']));
        $app->map('captcha', fn(): CaptchaService => $this->alwaysAcceptingCaptcha($app));
        $service = new CommentService($this->pdo, $app);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Captcha verification failed.');

        $service->create([
            'commentable_type' => 'blog',
            'commentable_id'   => 1,
            'body'             => 'Hello world',
            'ip_address'       => '127.0.0.1',
        ]);
    }

    public function testCreateStoresWhenTheProviderAccepts(): void
    {
        $app = $this->buildApp();
        $app->settings()->set('Captcha.provider', 'hcaptcha');
        $app->settings()->set('Captcha.site_key', 'site-1');
        $app->settings()->set('Captcha.secret_key', 'secret-1');
        $app->settings()->set('Captcha.protected', json_encode(['comments']));
        $app->map('captcha', fn(): CaptchaService => $this->alwaysAcceptingCaptcha($app));
        $service = new CommentService($this->pdo, $app);

        $comment = $service->create([
            'commentable_type' => 'blog',
            'commentable_id'   => 1,
            'body'             => 'Hello world',
            'ip_address'       => '127.0.0.1',
            'guest_name'       => 'Ada',
            'captcha_token'    => 'tok',
        ]);

        self::assertSame(1, $this->commentCount());
        self::assertSame('pending', (string) $comment->status);
        self::assertSame('Hello world', (string) $comment->body);
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
     * CaptchaService with the provider call stubbed to always accept, so
     * the tests exercise enforcement without any network traffic.
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

    private function commentCount(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS c FROM comments');

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }
}
