<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Profiles\Controllers;

use Pubvana\Controllers\Admin\AdminController;

class ProfilesAdminController extends AdminController
{
    public function index(): void
    {
        $user    = $this->app->auth()->user();
        $profile = $this->app->profiles()->findOrCreate((int) ($user?->id));
        $avatarPicker = $this->app->media()->avatarPicker('avatar', $profile->avatar ?? '');

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'My Profile',
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => $this->adminBase(),
            'adminBase'    => $this->adminBase(),
        ]);
    }

    public function show(string $userId): void
    {
        $currentUser = $this->app->auth()->user();
        if ((int) ($currentUser?->id) !== (int) $userId && !$currentUser?->can('profile.edit.any')) {
            $this->app->session()->flash('error', 'You do not have permission to edit other users\' profiles.');
            $this->app->redirect('/admin/users');
            return;
        }

        $userModel = new \Enlivenapp\FlightShield\Models\User($this->app->db());
        $user = $userModel->find((int) $userId);

        $profile    = $this->app->profiles()->findOrCreate((int) $userId);
        $avatarPicker = $this->app->media()->avatarPicker('avatar', $profile->avatar ?? '');

        $this->render('pubvana/profiles/admin/profile/index', [
            'pageTitle'    => 'Edit Profile — ' . htmlspecialchars((string) ($user->username ?? '')),
            'profile'      => $profile,
            'user'         => $user,
            'avatarPicker' => $avatarPicker,
            'returnUrl'    => '/admin/users/' . (int) $userId . '/edit',
            'adminBase'    => $this->adminBase(),
        ]);
    }

    public function update(string $userId): void
    {
        $currentUser = $this->app->auth()->user();
        if ((int) ($currentUser?->id) !== (int) $userId && !$currentUser?->can('profile.edit.any')) {
            $this->app->session()->flash('error', 'You do not have permission to edit other users\' profiles.');
            $this->app->redirect('/admin/users');
            return;
        }

        $post = $this->app->request()->data->getData();
        $returnUrl = $post['return_url'] ?? $this->adminBase();
        unset($post['_csrf_token'], $post['return_url']);

        $this->app->profiles()->updateProfile((int) $userId, $post);

        $this->app->session()->flash('success', 'Profile updated.');
        $this->app->redirect($returnUrl);
    }

    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/profiles'), '/');
    }
}
