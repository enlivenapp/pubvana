<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use DateTimeImmutable;
use Enlivenapp\FlightShield\Models\RememberToken;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\PasswordResetService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * PasswordResetService against an in-memory database.
 *
 * Covers the token lifecycle (issue, find, single-use consume), the
 * password change path (validation via Shield's UserManagement), the
 * force_reset flag handling, remember-token revocation, and the failed
 * attempt rows that feed Shield's rate limiter.
 *
 * The email sender and view are lightweight stand-ins; the plain reset
 * token is recovered from the emailed link, exactly like a real user
 * would recover it.
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(PasswordResetService::class)]
final class PasswordResetServiceTest extends TestCase
{
    private const SHIELD_CONFIG = [
        'default_group' => 'user',
        'passwords'     => [
            'min_length' => 8,
            'validators' => [
                \Enlivenapp\FlightShield\Passwords\CompositionValidator::class,
            ],
        ],
        'redirects'     => [
            'after_login'       => '/',
            'after_login_admin' => '/admin',
        ],
    ];

    private PDO $pdo;

    /** @var array<int, array{to: string, subject: string, body: string}> */
    public array $sentEmails = [];

    /** @var string[] Template names passed to the view stand-in. */
    public array $fetchedTemplates = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        $this->sentEmails = [];
        $this->fetchedTemplates = [];
    }

    // -----------------------------------------------------------------
    // Issue tokens
    // -----------------------------------------------------------------

    public function testIssueResetTokenCreatesSingleHashedIdentityAndSendsEmail(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();

        self::assertTrue($service->issueResetToken('ada@example.com'));

        $identity = $this->resetIdentityFor($user);
        self::assertNotNull($identity);
        self::assertSame(PasswordResetService::IDENTITY_TYPE, $identity->type);
        self::assertSame(64, strlen((string) $identity->secret), 'secret must be a sha256 hex hash');

        $expires = new DateTimeImmutable((string) $identity->expires);
        $diff = time() - $expires->getTimestamp();
        self::assertGreaterThan(-3605, $diff, 'expiry should be about one hour out');
        self::assertLessThan(-3555, $diff, 'expiry should be about one hour out');

        self::assertCount(1, $this->sentEmails);
        self::assertSame('ada@example.com', $this->sentEmails[0]['to']);
        self::assertSame(
            'enlivenapp/flight-shield/Email/password_reset_email',
            $this->fetchedTemplates[0] ?? ''
        );
        self::assertStringContainsString('/auth/reset-password?token=', $this->sentEmails[0]['body']);
    }

    public function testIssueResetTokenReplacesPreviousTokens(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();

        $service->issueResetToken('ada@example.com');
        $service->issueResetToken('ada@example.com');

        $count = (int) $this->resetIdentityCount($user);
        self::assertSame(1, $count, 'a new request must replace the old token');
    }

    public function testIssueResetTokenIsFalseForUnknownEmail(): void
    {
        $service = $this->makeService();

        self::assertFalse($service->issueResetToken('nobody@example.com'));
        self::assertCount(0, $this->sentEmails);
    }

    // -----------------------------------------------------------------
    // Token lookup
    // -----------------------------------------------------------------

    public function testFindUserByTokenResolvesValidToken(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();
        $service->issueResetToken('ada@example.com');

        $found = $service->findUserByToken($this->lastTokenFromEmail());

        self::assertNotNull($found);
        self::assertSame((int) $user->id, (int) $found->id);
    }

    public function testFindUserByTokenRejectsGarbageAndExpired(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();
        $service->issueResetToken('ada@example.com');
        $plain = $this->lastTokenFromEmail();

        // Garbage token
        self::assertNull($service->findUserByToken('nope'));

        // Age the identity past its expiry
        $this->pdo->exec(
            "UPDATE auth_identities SET expires='2000-01-01 00:00:00' "
            . "WHERE user_id={$user->id} AND type='" . PasswordResetService::IDENTITY_TYPE . "'"
        );

        self::assertNull($service->findUserByToken($plain));
    }

    // -----------------------------------------------------------------
    // Reset by token
    // -----------------------------------------------------------------

    public function testResetByTokenChangesPasswordAndConsumesToken(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();
        $service->issueResetToken('ada@example.com');
        $plain = $this->lastTokenFromEmail();

        $result = $service->resetByToken($plain, 'Quartz-Loaf-Mango-77', 'Quartz-Loaf-Mango-77');

        self::assertTrue($result->isOK());

        // Single use: identity gone
        self::assertNull($this->resetIdentityFor($user));

        // Password verifies against the email identity
        $emailIdentity = (new UserIdentity($this->pdo))->getEmailIdentity($user);
        self::assertNotNull($emailIdentity);
        $passwords = new \Enlivenapp\FlightShield\Passwords\Passwords(self::SHIELD_CONFIG['passwords']);
        self::assertTrue($passwords->verify('Quartz-Loaf-Mango-77', (string) $emailIdentity->secret2));

        // Token replay fails
        $replay = $service->resetByToken($plain, 'Another-Good-One-99', 'Another-Good-One-99');
        self::assertFalse($replay->isOK());
    }

    public function testResetByTokenKeepsTokenWhenPasswordInvalid(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();
        $service->issueResetToken('ada@example.com');
        $plain = $this->lastTokenFromEmail();

        // Confirm mismatch
        $mismatch = $service->resetByToken($plain, 'Quartz-Loaf-Mango-77', 'Different-Confirm-88');
        self::assertFalse($mismatch->isOK());
        self::assertNotNull($this->resetIdentityFor($user), 'token must survive a retryable failure');

        // Too weak (below min_length)
        $weak = $service->resetByToken($plain, 'short', 'short');
        self::assertFalse($weak->isOK());
        self::assertNotNull($this->resetIdentityFor($user));
    }

    public function testResetByTokenClearsForceResetAndRevokesRememberTokens(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', forceReset: true);
        $this->seedRememberToken($user);
        $service = $this->makeService();
        $service->issueResetToken('ada@example.com');

        $result = $service->resetByToken($this->lastTokenFromEmail(), 'Quartz-Loaf-Mango-77', 'Quartz-Loaf-Mango-77');

        self::assertTrue($result->isOK());
        self::assertFalse($service->requiresReset($user), 'force_reset must be cleared');

        $count = (int) $this->pdo
            ->query("SELECT COUNT(*) c FROM auth_remember_tokens WHERE user_id={$user->id}")
            ->fetch()['c'];
        self::assertSame(0, $count, 'remember tokens must be revoked');
    }

    // -----------------------------------------------------------------
    // Session (forced) reset
    // -----------------------------------------------------------------

    public function testResetForUserClearsForceReset(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com', forceReset: true);
        $service = $this->makeService();

        self::assertTrue($service->requiresReset($user));

        $result = $service->resetForUser($user, 'Quartz-Loaf-Mango-77', 'Quartz-Loaf-Mango-77');

        self::assertTrue($result->isOK());
        self::assertFalse($service->requiresReset($user));
    }

    public function testResetForUserRejectsMismatch(): void
    {
        $user = $this->seedUser('ada', 'ada@example.com');
        $service = $this->makeService();

        $result = $service->resetForUser($user, 'Quartz-Loaf-Mango-77', 'Not-The-Same-99');

        self::assertFalse($result->isOK());
        self::assertSame('The passwords do not match.', $result->reason());
    }

    // -----------------------------------------------------------------
    // Rate limiter feeding
    // -----------------------------------------------------------------

    public function testRecordFailureWritesPasswordResetLoginRow(): void
    {
        $service = $this->makeService();

        $service->recordFailure('reset');

        $count = (int) $this->pdo
            ->query("SELECT COUNT(*) c FROM auth_logins WHERE id_type='" . PasswordResetService::IDENTITY_TYPE . "' AND success=0")
            ->fetch()['c'];
        self::assertSame(1, $count);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Recover the plain token from the last sent email's reset link, the
     * same way a real recipient would.
     */
    private function lastTokenFromEmail(): string
    {
        self::assertNotEmpty($this->sentEmails, 'a reset email must have been sent');
        self::assertSame(1, preg_match('/reset-password\?token=([a-f0-9]{40})/', $this->sentEmails[0]['body'], $m));

        return $m[1];
    }

    private function makeService(): PasswordResetService
    {
        $test = $this;

        $app = $this->app([
            'db' => fn(): PDO => $this->pdo,
            // View stand-in: records fetched template names and echoes the
            // reset link into the body, the way Vision renders the template.
            'view' => fn(): object => new class($test) {
                public function __construct(private readonly PasswordResetServiceTest $test) {}
                public function fetch(string $file, ?array $data = null): string
                {
                    $this->test->fetchedTemplates[] = $file;
                    $url = is_array($data) ? (string) ($data['resetUrl'] ?? '') : '';

                    return '<a href="' . $url . '">body</a>';
                }
            },
            // Mailer stand-in: records sendHtml calls.
            'mailer' => fn(): object => new class($test) {
                public function __construct(private readonly PasswordResetServiceTest $test) {}
                public function sendHtml(string $to, string $subject, string $bodyHtml, array $opts = []): void
                {
                    $this->test->sentEmails[] = [
                        'to' => $to,
                        'subject' => $subject,
                        'body' => $bodyHtml,
                    ];
                }
            },
        ]);

        // Shield models talk to \Flight::db() (the global engine), so the
        // test engine must BE the global one.
        \Flight::setEngine($app);

        $app->set('enlivenapp.flight-shield', self::SHIELD_CONFIG);
        $app->set('CMS.siteUrl', 'http://localhost');

        return new PasswordResetService($app);
    }

    private function seedUser(string $username, string $email, bool $forceReset = false): User
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = $username;
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        (new UserIdentity($this->pdo))->createEmailIdentity($user, [
            'email'         => $email,
            'password_hash' => password_hash('Original-Seed-123', PASSWORD_DEFAULT),
        ]);

        if ($forceReset) {
            (new UserIdentity($this->pdo))->forcePasswordReset($user, true);
        }

        return $user;
    }

    private function seedRememberToken(User $user): void
    {
        $token = new RememberToken($this->pdo);
        $token->selector = bin2hex(random_bytes(12));
        $token->hashed_validator = hash('sha256', bin2hex(random_bytes(20)));
        $token->user_id = (int) $user->id;
        $token->expires = (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        $token->created_at = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $token->updated_at = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $token->insert();
    }

    private function resetIdentityFor(User $user): ?UserIdentity
    {
        $identity = new UserIdentity($this->pdo);
        $identity->eq('user_id', (int) $user->id)
                 ->eq('type', PasswordResetService::IDENTITY_TYPE)
                 ->find();

        return $identity->isHydrated() ? $identity : null;
    }

    private function resetIdentityCount(User $user): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) c FROM auth_identities WHERE user_id = :uid AND type = :type"
        );
        $stmt->execute([':uid' => (int) $user->id, ':type' => PasswordResetService::IDENTITY_TYPE]);

        return (int) $stmt->fetch()['c'];
    }
}
