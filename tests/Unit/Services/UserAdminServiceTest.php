<?php

declare(strict_types=1);

namespace Pubvana\Tests\Unit\Services;

use DateTimeImmutable;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use Pubvana\Services\UserAdminService;
use Pubvana\Tests\Support\Sqlite;
use Pubvana\Tests\Support\TestCase;

/**
 * UserAdminService (ban/unban/force reset) against an in-memory database.
 *
 * Persistence follows Shield's UserManagement::setActive() dirty() pattern;
 * these tests prove the writes actually land and read back through a fresh
 * model instance, mirroring Shield's login-time checks (isBanned()).
 *
 * @package Pubvana\Tests\Unit\Services
 */
#[CoversClass(UserAdminService::class)]
final class UserAdminServiceTest extends TestCase
{
    private PDO $pdo;

    private UserAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = Sqlite::recreate();
        \Flight::setEngine($this->app(['db' => fn(): PDO => $this->pdo]));
        $this->service = new UserAdminService(\Flight::app());
    }

    public function testBanPersistsStatusAndMessage(): void
    {
        $user = $this->seedUser('barnaby');

        $this->service->ban($user, 'Spamming the contact form');

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->isBanned());
        self::assertSame('banned', $fresh->status);
        self::assertSame('Spamming the contact form', $fresh->status_message);
        self::assertSame(1, (int) $fresh->active, 'banning must not deactivate the account');
    }

    public function testBanWithoutMessageStoresNullMessage(): void
    {
        $user = $this->seedUser('quietban');

        $this->service->ban($user, null);

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->isBanned());
        self::assertNull($fresh->status_message);
    }

    public function testUnBanClearsStatus(): void
    {
        $user = $this->seedUser('pardoned');
        $this->service->ban($user, 'oops');

        $this->service->unBan($user);

        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertFalse($fresh->isBanned());
        self::assertNull($fresh->status);
        self::assertNull($fresh->status_message);
    }

    public function testForceResetTogglesIdentityFlag(): void
    {
        $user = $this->seedUser('resetme');

        $this->service->forceReset($user, true);
        self::assertTrue($this->identityForceReset((int) $user->id));

        // The flag lives on the email identity; requiresPasswordReset() reads it.
        $fresh = $this->freshUser((int) $user->id);
        self::assertNotNull($fresh);
        self::assertTrue($fresh->requiresPasswordReset());

        $this->service->forceReset($user, false);
        self::assertFalse($this->identityForceReset((int) $user->id));
    }

    public function testForceResetWithNoEmailIdentityIsSilentNoOp(): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = 'no-identity';
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        $this->service->forceReset($user, true);

        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) c FROM auth_identities')->fetch()['c']);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function seedUser(string $username): User
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $user = new User($this->pdo);
        $user->username = $username;
        $user->active = true;
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->insert();

        (new UserIdentity($this->pdo))->createEmailIdentity($user, [
            'email'         => $username . '@example.com',
            'password_hash' => password_hash('Seed-Only-123', PASSWORD_DEFAULT),
        ]);

        return $user;
    }

    private function freshUser(int $id): ?User
    {
        return (new User($this->pdo))->findById($id);
    }

    private function identityForceReset(int $userId): bool
    {
        $identity = new UserIdentity($this->pdo);
        $identity->eq('user_id', $userId)
                 ->eq('type', UserIdentity::TYPE_EMAIL_PASSWORD)
                 ->find();

        return $identity->isHydrated() && (bool) $identity->force_reset;
    }
}
