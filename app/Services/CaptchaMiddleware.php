<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;

/**
 * CaptchaMiddleware - Sign-in captcha check.
 *
 * Runs the captcha check for captcha-protected core areas before the
 * request is handled. Currently covers the one core-owned area, the
 * sign-in form (Shield's POST /auth/login); plugins enforce their own
 * areas inside their submission handlers.
 *
 * Invoked inline during boot in app/config/services.php, immediately
 * after the CSRF gate and using the same pattern: read the superglobals,
 * decide, and on failure answer the request and stop (a state-changing
 * POST must never reach Shield's login handler unverified).
 *
 * @package Pubvana\Services
 */
class CaptchaMiddleware
{
    /**
     * @var Engine<object>
     */
    protected Engine $app;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Validate the captcha on the sign-in POST before it is handled.
     */
    public function before(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'POST') {
            return;
        }

        $loginPath = $this->loginPath();
        if ($loginPath === '') {
            return;
        }

        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if ($requestPath !== $loginPath) {
            return;
        }

        if (!$this->app->captcha()->enforcedFor('login')) {
            return;
        }

        $post = $this->app->request()->data->getData();
        $token = is_string($post[$this->app->captcha()->postField()] ?? null)
            ? (string) $post[$this->app->captcha()->postField()]
            : '';

        if ($this->app->captcha()->verify($token, (string) ($_SERVER['REMOTE_ADDR'] ?? ''))) {
            return;
        }

        $this->reject();
    }

    /**
     * Answer a failed sign-in captcha the same way Shield answers a failed
     * login: re-render the sign-in view with an error message, then stop.
     *
     * Renders through the explicit plugin prefix because this runs during
     * boot, before any route (and therefore any plugin view context) exists.
     */
    protected function reject(): void
    {
        $config = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);

        $this->app->render('enlivenapp/flight-shield/login', [
            'error'  => 'Captcha verification failed. Please try again.',
            'config' => $config,
        ]);

        exit;
    }

    /**
     * The sign-in POST path from the Shield config ('/auth/login').
     */
    protected function loginPath(): string
    {
        $config = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);
        $redirects = is_array($config['redirects'] ?? null) ? $config['redirects'] : [];

        return (string) ($redirects['login'] ?? '');
    }
}
