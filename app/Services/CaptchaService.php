<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * CaptchaService - Site-wide human verification.
 *
 * Single owner of captcha configuration and markup for every area that
 * wants it (comment forms, public forms, the sign-in form, ...). Areas
 * register through the adext 'captcha.area' type; each registered area
 * can be switched on or off in Settings > Captcha.
 *
 * Providers supported: hCaptcha and reCAPTCHA v2 (checkbox). Server-side
 * verification posts the token to the provider's siteverify endpoint and
 * fails closed when the secret key is missing or the provider cannot be
 * reached. A misconfiguration is never a hall-pass.
 *
 * Access anywhere with: $app->captcha()
 *
 * @package Pubvana\Services
 */
class CaptchaService
{
    /**
     * @var Engine<object>
     */
    protected Engine $app;

    /**
     * Known providers: their siteverify endpoint, the POST field the
     * widget submits, and the widget script/div class.
     */
    private const PROVIDERS = [
        'hcaptcha' => [
            'endpoint'   => 'https://api.hcaptcha.com/siteverify',
            'post_field' => 'h-captcha-response',
            'script'     => 'https://js.hcaptcha.com/1/api.js',
            'class'      => 'h-captcha',
        ],
        'recaptcha' => [
            'endpoint'   => 'https://www.google.com/recaptcha/api/siteverify',
            'post_field' => 'g-recaptcha-response',
            'script'     => 'https://www.google.com/recaptcha/api.js',
            'class'      => 'g-recaptcha',
        ],
    ];

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    // -----------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------

    /**
     * The active provider ('none', 'hcaptcha', 'recaptcha').
     */
    public function provider(): string
    {
        $provider = (string) $this->app->settings()->get('Captcha.provider', 'none');
        return isset(self::PROVIDERS[$provider]) ? $provider : 'none';
    }

    /**
     * The provider's public site key.
     */
    public function siteKey(): string
    {
        return (string) $this->app->settings()->get('Captcha.site_key', '');
    }

    /**
     * The provider's secret key.
     */
    public function secretKey(): string
    {
        return (string) $this->app->settings()->get('Captcha.secret_key', '');
    }

    /**
     * Whether a usable provider is configured (provider set and site key present).
     */
    public function isEnabled(): bool
    {
        return $this->provider() !== 'none' && $this->siteKey() !== '';
    }

    /**
     * The POST field name the current provider's widget submits ('' when none).
     */
    public function postField(): string
    {
        $provider = $this->provider();
        return self::PROVIDERS[$provider]['post_field'] ?? '';
    }

    // -----------------------------------------------------------------
    // Protected areas
    // -----------------------------------------------------------------

    /**
     * Every registered captcha area, keyed by area key.
     *
     * Areas register through adext type 'captcha.area' (core registers the
     * sign-in form; plugins register their own submission forms).
     *
     * @return array<string, array<string, mixed>> area key => registration config
     */
    public function areas(): array
    {
        return $this->app->adext()->get('captcha.area', 'default');
    }

    /**
     * Whether an area is switched on in Settings > Captcha.
     */
    public function isProtected(string $area): bool
    {
        $stored = $this->app->settings()->get('Captcha.protected', '[]');
        $areas = json_decode((string) $stored, true);
        if (!is_array($areas)) {
            return false;
        }

        return in_array($area, array_map('strval', $areas), true);
    }

    /**
     * Whether captcha must actually run for an area right now:
     * a provider is configured AND the area is switched on.
     */
    public function enforcedFor(string $area): bool
    {
        return $this->isEnabled() && $this->isProtected($area);
    }

    /**
     * Store the protected-area switch list.
     *
     * Posted keys not matching a registered area are dropped, so a stale
     * or forged checkbox can never enable protection for an unknown area.
     *
     * @param array<int|string, mixed> $areas Keys to protect (checkboxes post as area => '1')
     */
    public function setProtectedAreas(array $areas): void
    {
        $registered = array_map('strval', array_keys($this->areas()));
        $kept = [];

        foreach (array_keys($areas) as $area) {
            $area = (string) $area;
            if (in_array($area, $registered, true)) {
                $kept[] = $area;
            }
        }

        $this->app->settings()->set('Captcha.protected', json_encode(array_values(array_unique($kept))));
    }

    // -----------------------------------------------------------------
    // Markup
    // -----------------------------------------------------------------

    /**
     * The widget markup for an area: empty unless captcha is enforced there.
     *
     * This is the single source of the widget snippet, so every area renders
     * the right provider's div and script (comments templates previously
     * printed a reCAPTCHA div even when hCaptcha was configured).
     */
    public function snippetFor(string $area): string
    {
        return $this->enforcedFor($area) ? $this->snippet() : '';
    }

    /**
     * The raw widget markup for the configured provider.
     */
    public function snippet(): string
    {
        $provider = $this->provider();
        if (!isset(self::PROVIDERS[$provider]) || $this->siteKey() === '') {
            return '';
        }

        $siteKey = htmlspecialchars($this->siteKey(), ENT_QUOTES);

        return '<div class="' . self::PROVIDERS[$provider]['class'] . '" data-sitekey="' . $siteKey . '"></div>' . "\n"
            . '<script src="' . self::PROVIDERS[$provider]['script'] . '" async defer></script>';
    }

    // -----------------------------------------------------------------
    // Verification
    // -----------------------------------------------------------------

    /**
     * Verify a token against the provider's siteverify endpoint.
     *
     * Returns true when captcha is not configured (nothing to check) and
     * fails closed on a missing secret, an unreachable provider, or an
     * unparseable response.
     */
    public function verify(string $token, string $remoteIp): bool
    {
        $provider = $this->provider();
        if ($provider === 'none' || !isset(self::PROVIDERS[$provider])) {
            return true;
        }

        $secret = $this->secretKey();
        if ($secret === '') {
            return false;
        }

        if ($token === '') {
            return false;
        }

        $result = $this->postToSiteverify(self::PROVIDERS[$provider]['endpoint'], [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        return is_array($result) && ($result['success'] ?? false) === true;
    }

    /**
     * Verify the provider's token found in a submitted data array.
     *
     * Convenience for submission handlers: reads the provider's POST field
     * from the array and verifies it against the client IP.
     *
     * @param array<string, mixed> $data Submitted data (e.g. $_POST)
     */
    public function verifySubmission(array $data, string $remoteIp): bool
    {
        $field = $this->postField();
        if ($field === '') {
            return true;
        }

        $token = $data[$field] ?? null;
        if (!is_string($token)) {
            return false;
        }

        return $this->verify($token, $remoteIp);
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * POST the verification payload to a siteverify endpoint.
     *
     * Separated from verify() so tests can stub the network call.
     *
     * @param array<string, string> $payload
     * @return array<string, mixed>|null Decoded JSON response, or null on failure
     */
    protected function postToSiteverify(string $endpoint, array $payload): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($payload),
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            return null;
        }

        $decoded = json_decode($response, true);

        return is_array($decoded) ? $decoded : null;
    }
}
