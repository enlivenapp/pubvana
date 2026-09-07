<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Public;

use Enlivenapp\FlightShield\Models\User;
use flight\Engine;
use Pubvana\Services\PasswordResetService;

/**
 * PasswordResetController - Forgot-password and reset flows (public).
 *
 * Two modes share /auth/reset-password:
 *   - Token mode: an anonymous visitor follows an emailed link and sets a
 *     new password.
 *   - Session mode: a logged-in user carrying Shield's force_reset flag
 *     (ForcePasswordResetMiddleware redirect) sets a new password directly.
 *
 * HTTP only, strict MVC: identity and email work live in
 * PasswordResetService.
 *
 * @package Pubvana\Controllers\Public
 */
class PasswordResetController
{
    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    protected PasswordResetService $resets;

    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
        $this->resets = new PasswordResetService($app);
    }

    // -----------------------------------------------------------------
    // Forgot password
    // -----------------------------------------------------------------

    /**
     * Forgot-password form. Logged-in users have no business here.
     */
    public function forgotForm(): void
    {
        if ($this->app->auth()->loggedIn()) {
            $this->app->redirect($this->afterLoginUrl());
            return;
        }

        $this->renderAuthPage('enlivenapp/flight-shield/auth/forgot', [
            'error' => null,
        ], 'Forgot Password', 'We will email you a reset link');
    }

    /**
     * Send a reset link. The response is identical whether or not the
     * email belongs to an account, so the endpoint cannot enumerate users.
     */
    public function sendResetLink(): void
    {
        $email = trim((string) ($this->app->request()->data->email ?? ''));

        $this->resets->recordFailure($email);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->renderAuthPage('enlivenapp/flight-shield/auth/forgot', [
                'error' => 'Enter a valid email address.',
            ], 'Forgot Password', 'We will email you a reset link');
            return;
        }

        try {
            $this->resets->issueResetToken($email);
            $this->renderAuthPage('enlivenapp/flight-shield/auth/message', [
                'text'  => 'If an account exists for that address, a password reset link is on its way. The link expires in one hour.',
            ], 'Check your email', null);
        } catch (\RuntimeException $e) {
            error_log('PasswordResetController::sendResetLink - ' . $e->getMessage());
            $this->renderAuthPage('enlivenapp/flight-shield/auth/message', [
                'text'  => 'The reset email could not be sent. Please try again shortly or contact the site administrator.',
                'error' => true,
            ], 'Email problem', null);
        }
    }

    // -----------------------------------------------------------------
    // Reset password
    // -----------------------------------------------------------------

    /**
     * Reset form: token mode for link followers, session mode for forced
     * resets, error state for dead tokens.
     */
    public function resetForm(): void
    {
        $user = $this->app->auth()->user();

        // Forced reset: logged in with the flag set, no token involved.
        if ($user !== null && $this->resets->requiresReset($user)) {
            $this->renderReset(null, null, true);
            return;
        }

        $token = (string) ($this->app->request()->query->token ?? '');

        if ($token === '') {
            if ($user !== null) {
                $this->app->redirect($this->afterLoginUrl());
                return;
            }
            $this->app->redirect('/auth/forgot');
            return;
        }

        // Logged-in users without a pending reset just follow the token
        // flow like everyone else.
        $tokenUser = $this->resets->findUserByToken($token);

        if ($tokenUser === null) {
            $this->renderAuthPage('enlivenapp/flight-shield/auth/message', [
                'text'      => 'This reset link is invalid or has expired. You can request a new one.',
                'error'     => true,
                'forgotUrl' => true,
            ], 'Link expired', null);
            return;
        }

        $this->renderReset($token, null, false);
    }

    /**
     * Process the new password, in whichever mode the request arrived.
     */
    public function processReset(): void
    {
        $post = $this->app->request()->data;
        $password = (string) ($post->password ?? '');
        $passwordConfirm = (string) ($post->password_confirm ?? '');

        $user = $this->app->auth()->user();

        // Session mode first: forced reset beats a token if both appear.
        if ($user !== null && $this->resets->requiresReset($user)) {
            $result = $this->resets->resetForUser($user, $password, $passwordConfirm);

            if (!$result->isOK()) {
                $this->renderReset(null, $result->reason(), true);
                return;
            }

            $this->app->redirect($this->afterLoginUrl());
            return;
        }

        // Token mode.
        $token = trim((string) ($post->token ?? ''));

        if ($token === '') {
            $this->app->redirect('/auth/forgot');
            return;
        }

        $this->resets->recordFailure('reset');

        $result = $this->resets->resetByToken($token, $password, $passwordConfirm);

        if (!$result->isOK()) {
            $this->renderReset($token, $result->reason(), false);
            return;
        }

        $this->renderAuthPage('enlivenapp/flight-shield/auth/message', [
            'text' => 'Your password has been updated. Sign in with your new password.',
        ], 'Password updated', null);
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    /**
     * Render the reset form. Token mode keeps the token in a hidden field;
     * session mode shows no token UI.
     */
    protected function renderReset(?string $token, ?string $error, bool $sessionMode): void
    {
        $this->renderAuthPage('enlivenapp/flight-shield/auth/reset', [
            'token'       => $token ?? '',
            'error'       => $error,
            'sessionMode' => $sessionMode,
        ], 'Set a New Password', null);
    }

    /**
     * Render an auth page: body partial wrapped in the shared auth layout.
     *
     * All names ride the enlivenapp/flight-shield plugin-prefix chain so the
     * overrides resolve to the active theme (themes/{name}/Views/
     * enlivenapp/flight-shield/...), matching the Shield view overrides.
     *
     * @param array<string, mixed> $data Vars for the partial
     */
    protected function renderAuthPage(string $partial, array $data, string $title, ?string $subtitle): void
    {
        $this->syncThemePath();

        $content = $this->app->view()->fetch($partial, $data);

        $this->app->render('enlivenapp/flight-shield/auth/layout', [
            'content'      => $content,
            'authTitle'    => $title,
            'authSubtitle' => $subtitle,
        ]);
    }

    /**
     * Point the theme override tier at the active theme from the database.
     *
     * These are core routes with no plugin-view middleware, and
     * services.php keys themePath off the 'active_theme' config key, which
     * nothing sets (see PublicController::render for the same sync on
     * public pages).
     */
    protected function syncThemePath(): void
    {
        $view = $this->app->view();

        if (!$view instanceof \Pubvana\Services\PluginView) {
            return;
        }

        try {
            $activeTheme = $this->app->themes()->getActive();

            if ($activeTheme === null) {
                return;
            }

            $activeThemePath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes'
                . DIRECTORY_SEPARATOR . $activeTheme->folder
                . DIRECTORY_SEPARATOR . 'Views';

            if ($view->getThemePath() !== rtrim($activeThemePath, DIRECTORY_SEPARATOR)) {
                $view->setThemePath($activeThemePath);
            }
        } catch (\Throwable) {
            // Themes table missing on fresh installs; boot already placed
            // a fallback theme path.
        }
    }

    /**
     * Where a logged-in user lands after finishing work here.
     */
    protected function afterLoginUrl(): string
    {
        $config = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);
        $redirects = (array) ($config['redirects'] ?? []);

        $user = $this->app->auth()->user();

        if ($user !== null && $user->can('admin.access')) {
            return (string) ($redirects['after_login_admin'] ?? '/admin');
        }

        return (string) ($redirects['after_login'] ?? '/');
    }

    /**
     * @return string
     */
    protected function siteName(): string
    {
        return (string) ($this->app->get('CMS.siteName') ?? 'Pubvana');
    }
}
