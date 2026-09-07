<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use flight\Engine;

/**
 * UserAdminService - Account status actions Shield's UserManagement
 * does not expose: ban, unban, and forced password reset.
 *
 * Persistence follows the same dirty() pattern UserManagement::setActive()
 * uses: typed property writes bypass ActiveRecord's dirty tracker, so
 * changed fields are pushed explicitly before save().
 *
 * Banned users are already blocked by Shield at login (attempt() and the
 * remember-me path both reject status 'banned'); no additional checking
 * is needed here.
 *
 * @package Pubvana\Services
 */
class UserAdminService
{
    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Ban a user, with an optional message shown in the admin UI.
     * The user can no longer log in.
     */
    public function ban(User $user, ?string $message = null): void
    {
        $user->ban($message !== null && $message !== '' ? $message : null);

        $this->saveStatus($user, 'banned', $user->getBanMessage());
    }

    /**
     * Lift a ban. The account's active flag is untouched.
     */
    public function unBan(User $user): void
    {
        $user->unBan();

        $this->saveStatus($user, null, null);
    }

    /**
     * Set or clear Shield's force_reset flag on the user's email identity.
     *
     * With the flag set, any request the user makes through a route guarded
     * by ForcePasswordResetMiddleware redirects to the reset page until a
     * new password is saved.
     */
    public function forceReset(User $user, bool $force): void
    {
        (new UserIdentity($this->app->db()))->forcePasswordReset($user, $force);
    }

    /**
     * Persist status fields, mirroring UserManagement::setActive().
     */
    protected function saveStatus(User $user, ?string $status, ?string $statusMessage): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $user->updated_at = $now;
        $user->dirty([
            'status'         => $status,
            'status_message' => $statusMessage,
            'updated_at'     => $now,
        ]);
        $user->save();
    }
}
