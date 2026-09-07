<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Enlivenapp\FlightShield\Models\User;
use Enlivenapp\FlightShield\Models\UserIdentity;
use flight\Engine;
use Pubvana\Services\UserAdminService;

/**
 * UsersController - Admin CRUD for user management.
 *
 * Handles listing, creating, editing, deleting, and toggling
 * active status for users. All data access goes through
 * flight-shield's management API (auth()->users(), auth()->groups()).
 *
 * Strict MVC: this controller handles HTTP only. Views handle display.
 *
 * @package Pubvana\Controllers\Admin
 */
class UsersController extends AdminController
{
    /**
     * Superadmin visibility flag per Shield's convention:
     * superadmins see everyone, everyone else sees non-superadmins.
     */
    protected function viewerIsSuperadmin(): bool
    {
        return $this->app->auth()->user()?->inGroup('superadmin') ?? false;
    }

    /**
     * User listing with pagination.
     *
     * Reads page number from query string, fetches paginated users,
     * and renders the index view with a table of all users.
     *
     * @return void
     */
    public function index(): void
    {
        $request = $this->app->request();
        $page = max(1, (int) ($request->query->page ?? 1));
        $perPage = 20;
        $includeSuperadmins = $this->viewerIsSuperadmin();

        $users = $this->app->auth()->users()->paginated($page, $perPage, $includeSuperadmins);
        $total = $this->app->auth()->users()->count($includeSuperadmins);

        $this->render('admin/users/index', [
            'pageTitle' => 'Users',
            'users'     => $users,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
        ]);
    }

    /**
     * Create user form.
     *
     * Shows a form with username, email, password, and group selection.
     *
     * @return void
     */
    public function create(): void
    {
        $groups = $this->app->auth()->groups()->all();

        $this->render('admin/users/create', [
            'pageTitle' => 'New User',
            'groups'    => $groups,
        ]);
    }

    /**
     * Store a new user.
     *
     * Creates the user through flight-shield (password validation,
     * duplicate-email guard, hashing, group assignment) and redirects
     * to the user listing.
     *
     * @return void
     */
    public function store(): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $result = $this->app->auth()->users()->create(
            (string) ($post['username'] ?? ''),
            (string) ($post['email'] ?? ''),
            (string) ($post['password'] ?? ''),
            isset($post['group']) && $post['group'] !== '' ? (string) $post['group'] : null
        );

        if (!$result->isOK()) {
            $this->app->session()->flash('error', $result->reason() ?: 'Failed to create user.');
            $this->app->redirect('/admin/users/create');
            return;
        }

        $this->app->session()->flash('success', 'User created.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Invite a user to register.
     *
     * Takes an email address, validates that registration is available and
     * the address is not already registered, and emails an invitation
     * pointing to the public registration page.
     *
     * @return void
     */
    public function invite(): void
    {
        $email = trim((string) ($this->app->request()->data->email ?? ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->app->session()->flash('error', 'Enter a valid email address.');
            $this->app->redirect('/admin/users');
            return;
        }

        $shield = (array) ($this->app->get('enlivenapp.flight-shield') ?? []);
        if (!($shield['allow_registration'] ?? true)) {
            $this->app->session()->flash('error', 'Registration is disabled, so invitations cannot be sent.');
            $this->app->redirect('/admin/users');
            return;
        }

        $existing = (new UserIdentity($this->app->db()))
            ->getIdentityBySecret(UserIdentity::TYPE_EMAIL_PASSWORD, $email);
        if ($existing !== null) {
            $this->app->session()->flash('error', 'A user with that email address already exists.');
            $this->app->redirect('/admin/users');
            return;
        }

        $siteName = $this->app->settings()->get('CMS.siteName') ?? 'Pubvana';
        $registerUrl = rtrim($this->baseUrl(), '/') . '/auth/register';

        $bodyHtml = '<p>You have been invited to join ' . htmlspecialchars($siteName) . '.</p>'
            . '<p><a href="' . htmlspecialchars($registerUrl) . '">Create your account</a></p>'
            . '<p>If the link does not work, copy this address into your browser: '
            . htmlspecialchars($registerUrl) . '</p>';
        $alt = 'You have been invited to join ' . $siteName . ".\n\n"
            . 'Create your account here: ' . $registerUrl;

        try {
            $this->app->mailer()->sendHtml(
                $email,
                'You\'re invited to ' . $siteName,
                $bodyHtml,
                ['alt' => $alt]
            );
        } catch (\RuntimeException $e) {
            error_log('UsersController::invite - ' . $e->getMessage());
            $this->app->session()->flash('error', 'Failed to send the invitation. Check the mail settings.');
            $this->app->redirect('/admin/users');
            return;
        }

        $this->app->session()->flash('success', 'Invitation sent to ' . $email . '.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Resolve the absolute site base URL for links in outbound mail.
     *
     * Prefers the CMS.siteUrl setting (declared as the absolute base used
     * for generated links and emails). Falls back to deriving a scheme from
     * the HTTPS policy and the request host when it is not configured.
     *
     * @return string Absolute base URL, no trailing slash
     */
    protected function baseUrl(): string
    {
        $siteUrl = trim((string) ($this->app->settings()->get('CMS.siteUrl') ?? ''));

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
     * Edit user form.
     *
     * Shows a form pre-filled with the user's current data,
     * including group membership and direct permissions.
     *
     * @param string $id User ID
     * @return void
     */
    public function edit(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        $groups = $this->app->auth()->groups()->all();

        $this->render('admin/users/edit', [
            'pageTitle'       => 'Edit User',
            'editUser'        => $user,
            'groups'          => $groups,
            'userGroups'      => $user->getGroups(),
            'userPermissions' => $user->getPermissions(),
            'email'           => $this->app->auth()->users()->getEmail($user) ?? '',
            'banned'          => $user->isBanned(),
            'banMessage'      => $user->getBanMessage(),
            'requiresReset'   => $user->requiresPasswordReset(),
        ]);
    }

    /**
     * Update a user.
     *
     * Updates username, email, password (if provided), and group membership.
     * Only updates fields that are present in the POST data.
     *
     * @param string $id User ID
     * @return void
     */
    public function update(string $id): void
    {
        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        /** @var User|null $user */
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        $data = [];
        if (isset($post['username'])) {
            $data['username'] = $post['username'];
        }
        if (isset($post['email'])) {
            $data['email'] = $post['email'];
        }
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }

        // Profile update may fail validation (e.g. weak password) — the
        // user record itself is untouched in that case; redirect is silent
        // to match this admin UI's no-flash-message convention.
        $this->app->auth()->users()->updateProfile($user, $data);

        // Sync groups regardless of profile outcome so checkbox state persists
        $groups = $post['groups'] ?? [];
        $user->syncGroups(is_array($groups) ? array_values($groups) : []);

        $this->app->session()->flash('success', 'User updated.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Soft-delete a user.
     *
     * Sets the deleted_at timestamp. The user record is preserved
     * but excluded from all future queries.
     *
     * @param string $id User ID
     * @return void
     */
    public function delete(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user !== null) {
            $this->app->auth()->users()->delete($user);
        }

        $this->app->session()->flash('success', 'User deleted.');
        $this->app->redirect('/admin/users');
    }

    /**
     * Toggle user active status.
     *
     * Flips the active flag between 0 and 1. Active users can log in,
     * inactive users are blocked.
     *
     * @param string $id User ID
     * @return void
     */
    public function toggle(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user !== null) {
            $this->app->auth()->users()->setActive($user, !$user->active);
        }

        $this->app->session()->flash('success', 'User status toggled.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Ban a user with an optional message.
     *
     * Banned users are rejected by Shield at login (and remember-me).
     * Admins cannot ban themselves.
     *
     * @param string $id User ID
     * @return void
     */
    public function ban(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user === null) {
            $this->app->redirect('/admin/users');
            return;
        }

        if ((string) $user->id === (string) $this->app->auth()->id()) {
            $this->app->session()->flash('error', 'You cannot ban your own account.');
            $this->app->redirect('/admin/users/' . $id . '/edit');
            return;
        }

        $message = trim((string) ($this->app->request()->data->ban_message ?? ''));

        $this->userAdmin()->ban($user, $message !== '' ? $message : null);

        $this->app->session()->flash('success', 'User banned.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Lift a ban.
     *
     * @param string $id User ID
     * @return void
     */
    public function unban(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user !== null) {
            $this->userAdmin()->unBan($user);
        }

        $this->app->session()->flash('success', 'Ban lifted.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Toggle Shield's force password reset flag for a user.
     *
     * With the flag set, the user's next guarded request redirects to the
     * reset page until a new password is saved.
     *
     * @param string $id User ID
     * @return void
     */
    public function forceReset(string $id): void
    {
        $user = $this->app->auth()->users()->find((int) $id, $this->viewerIsSuperadmin());

        if ($user !== null) {
            $this->userAdmin()->forceReset($user, !$user->requiresPasswordReset());
        }

        $this->app->session()->flash('success', 'Password reset requirement updated.');
        $this->app->redirect('/admin/users/' . $id . '/edit');
    }

    /**
     * Account status actions (ban/unban/force reset) via the local service.
     */
    protected function userAdmin(): UserAdminService
    {
        return new UserAdminService($this->app);
    }
}
