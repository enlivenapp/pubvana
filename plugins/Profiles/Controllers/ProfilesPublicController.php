<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Controllers;

use Pubvana\Controllers\Public\PublicController;
use Enlivenapp\FlightShield\Models\User;

class ProfilesPublicController extends PublicController
{
    public function __construct(\flight\Engine $app)
    {
        parent::__construct($app, 'pubvana.profiles');
    }

    public function show(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }

        $profile = $this->app->profiles()->findOrCreate((int) $user->id);

        $auth = $this->app->auth();
        $isOwner = $auth->loggedIn() && (int) ($auth->user()?->id) === (int) $user->id;

        $avatarUrl = '';
        if (!empty($profile->avatar)) {
            $avatarUrl = '/' . ltrim($profile->avatar, '/');
        }

        $this->render('profile', [
            'title'      => ($profile->display_name ?? $user->username) . "'s Profile",
            'profile'    => $profile,
            'user'       => $user,
            'isOwner'    => $isOwner,
            'avatar_url' => $avatarUrl,
        ]);
    }

    public function edit(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }
        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->session()->flash('danger', 'You can only edit your own profile.');
            $this->app->redirect('/');
            return;
        }

        $profile = $this->app->profiles()->findOrCreate((int) $user->id);

        $this->render('profile_edit', [
            'title'   => 'Edit Profile',
            'profile' => $profile,
            'user'    => $user,
        ]);
    }

    public function update(string $username): void
    {
        $user = $this->findUserByUsername($username);
        if ($user === null) {
            $this->app->halt(404, 'User not found');
            return;
        }
        if ((int) ($this->app->auth()->user()?->id) !== (int) $user->id) {
            $this->app->session()->flash('danger', 'You can only edit your own profile.');
            $this->app->redirect('/' . $this->getRoutePrepend() . '/' . $username);
            return;
        }

        $post = $this->app->request()->data->getData();
        unset($post['_csrf_token']);

        $this->app->profiles()->updateProfile((int) $user->id, $post);

        $this->app->redirect('/' . $this->getRoutePrepend() . '/' . $username);
    }

    protected function findUserByUsername(string $username): ?User
    {
        $userModel = new User($this->app->db());
        return $userModel->findByCredentials(['username' => $username]);
    }
}
