<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Enlivenapp\FlightShield\Models\Login;
use Enlivenapp\FlightShield\Models\RememberToken;
use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use Enlivenapp\FlightShield\Result;
use Enlivenapp\FlightShield\Services\UserManagement;
use flight\Engine;

/**
 * PasswordResetService - Forgot-password and forced-reset flows.
 *
 * Built on Shield's identity primitives: reset tokens are code identities
 * (hashed secret, expiry, single use) in the auth_identities table, and the
 * password change itself runs through Shield's UserManagement so strength
 * validators and hashing follow the configured Shield settings.
 *
 * Two entry points:
 *   - Token flow: an anonymous visitor requests a reset link by email
 *     (issueResetToken), then sets a new password with the link's token
 *     (resetByToken).
 *   - Session flow: a logged-in user carrying Shield's force_reset flag is
 *     redirected by ForcePasswordResetMiddleware and sets a new password
 *     (resetForUser).
 *
 * Both clear the force_reset flag and revoke remember-me tokens on success,
 * so stale devices cannot ride out a password change.
 *
 * @package Pubvana\Services
 */
class PasswordResetService
{
    /** Identity type for reset tokens (auth_identities.type) */
    public const IDENTITY_TYPE = 'password_reset';

    /** Reset link validity window in seconds */
    public const TOKEN_LIFETIME = 3600;

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /** @var array<string, mixed> Merged flight-shield config */
    protected array $config;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->config = (array) ($app->get('enlivenapp.flight-shield') ?? []);
    }

    // -----------------------------------------------------------------
    // Token flow
    // -----------------------------------------------------------------

    /**
     * Create a single-use reset token for the given email and send the link.
     *
     * Returns false when no user matches the email; the controller renders
     * the same response either way so probes cannot enumerate accounts.
     * Any previous reset token for the user is replaced.
     */
    public function issueResetToken(string $email): bool
    {
        $user = (new User($this->app->db()))->findByCredentials(['email' => $email]);

        if ($user === null) {
            return false;
        }

        $identityModel = new UserIdentity($this->app->db());
        $identityModel->deleteIdentitiesByType($user, self::IDENTITY_TYPE);

        $token = bin2hex(random_bytes(20));

        $identityModel->createCodeIdentity(
            $user,
            [
                'type'    => self::IDENTITY_TYPE,
                'name'    => 'password_reset',
                'extra'   => 'Password reset requested.',
                'expires' => (new \DateTimeImmutable('+' . self::TOKEN_LIFETIME . ' seconds')),
            ],
            static fn(): string => $token
        );

        $this->sendResetEmail($user, $token);

        return true;
    }

    /**
     * The user a reset token belongs to, when the token is valid and unexpired.
     */
    public function findUserByToken(string $token): ?User
    {
        $identity = $this->findIdentityByToken($token);

        if ($identity === null) {
            return null;
        }

        return (new User($this->app->db()))->findById((int) $identity->user_id);
    }

    /**
     * Set a new password from a reset token.
     *
     * The identity is deleted only on success: a failed attempt (weak
     * password, mismatch) leaves the token usable for its remaining
     * lifetime so the user can retry without requesting a new email.
     */
    public function resetByToken(string $token, string $password, string $passwordConfirm): Result
    {
        $identity = $this->findIdentityByToken($token);

        if ($identity === null) {
            return (new Result())
                ->setSuccess(false)
                ->setReason('This reset link is invalid or has expired. Request a new one.');
        }

        $user = (new User($this->app->db()))->findById((int) $identity->user_id);

        if ($user === null) {
            return (new Result())
                ->setSuccess(false)
                ->setReason('This reset link is invalid or has expired. Request a new one.');
        }

        $result = $this->applyNewPassword($user, $password, $passwordConfirm);

        if (!$result->isOK()) {
            return $result;
        }

        $identity->delete();

        return $result;
    }

    // -----------------------------------------------------------------
    // Session flow (forced reset)
    // -----------------------------------------------------------------

    /**
     * Does this logged-in user have a forced password reset pending?
     */
    public function requiresReset(User $user): bool
    {
        return $user->requiresPasswordReset();
    }

    /**
     * Set a new password for a logged-in user (forced reset flow).
     */
    public function resetForUser(User $user, string $password, string $passwordConfirm): Result
    {
        return $this->applyNewPassword($user, $password, $passwordConfirm);
    }

    // -----------------------------------------------------------------
    // Rate limiter feeding
    // -----------------------------------------------------------------

    /**
     * Record a failed auth attempt row so Shield's RateLimitMiddleware
     * counts reset-endpoint abuse into the same per-IP window as logins.
     */
    public function recordFailure(string $identifier): void
    {
        $login = new Login($this->app->db());
        $login->id_type    = self::IDENTITY_TYPE;
        $login->identifier = $identifier;
        $login->success    = false;
        $login->user_id    = null;
        $login->ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $login->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $login->date       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $login->insert();
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    /**
     * The unexpired reset identity for a raw token, or null.
     */
    protected function findIdentityByToken(string $token): ?UserIdentity
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $identity = (new UserIdentity($this->app->db()))
            ->getIdentityBySecret(self::IDENTITY_TYPE, hash('sha256', $token));

        if ($identity === null || $identity->isExpired()) {
            return null;
        }

        return $identity;
    }

    /**
     * Validate, hash, and store the new password, then close out the
     * reset state: clear force_reset, revoke remember-me tokens.
     */
    protected function applyNewPassword(User $user, string $password, string $passwordConfirm): Result
    {
        if ($password === '' || $password !== $passwordConfirm) {
            return (new Result())
                ->setSuccess(false)
                ->setReason('The passwords do not match.');
        }

        $result = $this->userManagement()->updateProfile($user, ['password' => $password]);

        if (!$result->isOK()) {
            return $result;
        }

        (new UserIdentity($this->app->db()))->forcePasswordReset($user, false);
        (new RememberToken($this->app->db()))->deleteByUser((int) $user->id);

        return (new Result())
            ->setSuccess(true)
            ->setExtraInfo($user);
    }

    /**
     * Email the reset link. Failures propagate: the controller catches and
     * flashes, since a silent failure would leave the user waiting forever.
     */
    protected function sendResetEmail(User $user, string $token): void
    {
        $identity = (new UserIdentity($this->app->db()))->getEmailIdentity($user);
        $to = $identity?->secret;

        if ($to === null || $to === '') {
            return;
        }

        $resetUrl = $this->baseUrl() . '/auth/reset-password?token=' . urlencode($token);
        $siteName = (string) ($this->app->get('CMS.siteName') ?? 'Pubvana');

        $body = $this->app->view()->fetch('enlivenapp/flight-shield/Email/password_reset_email', [
            'resetUrl'  => $resetUrl,
            'siteName'  => $siteName,
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'date'      => date('Y-m-d H:i:s'),
        ]);

        $alt = 'A password reset was requested for your ' . $siteName . " account.\n\n"
            . 'Set a new password here: ' . $resetUrl . "\n\n"
            . 'The link expires in one hour. If you did not request this, you can safely ignore it.';

        $this->app->mailer()->sendHtml($to, 'Reset your password - ' . $siteName, $body, ['alt' => $alt]);
    }

    /**
     * Resolve the absolute site base URL for the reset link.
     *
     * Prefers the CMS.siteUrl setting; falls back to deriving a scheme from
     * the HTTPS policy and the request host.
     */
    protected function baseUrl(): string
    {
        $siteUrl = trim((string) ($this->app->get('CMS.siteUrl') ?? ''));

        if ($siteUrl !== '') {
            return rtrim($siteUrl, '/');
        }

        $request = $this->app->request();
        $https = $this->app->get('flight.force_https') === true || (bool) ($request->secure ?? false);
        $scheme = $https ? 'https' : 'http';
        $host = $request->getHeader('Host') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $path = rtrim((string) ($request->base ?? ''), '/');

        return $scheme . '://' . $host . $path;
    }

    /**
     * Shield's high-level user API: password validation, hashing, identity save.
     */
    protected function userManagement(): UserManagement
    {
        return new UserManagement($this->app->db(), $this->config);
    }
}
