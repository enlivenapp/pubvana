<?php

namespace App\Controllers\Admin;

use App\Models\AuthorProfileModel;
use App\Services\MediaService;
use CodeIgniter\Shield\Models\UserModel;

class Users extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $db    = db_connect();
        $users = $db->table('users u')
            ->select('u.id, u.username, u.active, u.created_at, ai.secret AS email, g.group AS role')
            ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = \'email_password\'', 'left')
            ->join('auth_groups_users g', 'g.user_id = u.id', 'left')
            ->orderBy('u.created_at', 'DESC')
            ->get()->getResultObject();

        return $this->adminView('users/index', array_merge($this->baseData('Users', 'users'), [
            'users' => $users,
        ]));
    }

    public function edit(int $id): string
    {
        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $groups       = config('AuthGroups')->groups;
        $currentGroup = $user->getGroups()[0] ?? '';

        return $this->adminView('users/edit', array_merge($this->baseData('Edit User', 'users'), [
            'subject_user' => $user,
            'groups'       => $groups,
            'current_group'=> $currentGroup,
        ]));
    }

    public function update(int $id)
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin/users')->with('error', 'Permission denied.');
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $group = $this->request->getPost('group');
        if ($group && array_key_exists($group, config('AuthGroups')->groups)) {
            foreach ($user->getGroups() as $g) {
                $user->removeGroup($g);
            }
            $user->addGroup($group);
        }

        return redirect()->to('/admin/users')->with('success', 'User updated.');
    }

    public function delete(int $id)
    {
        if ($id === auth()->id()) {
            return redirect()->to('/admin/users')->with('error', 'Cannot delete yourself.');
        }
        $userModel = new UserModel();
        $userModel->delete($id, true);
        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    public function profile(int $id): string
    {
        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $profileModel = new AuthorProfileModel();
        $profile      = $profileModel->getByUserId($id) ?? (object) [];

        return $this->adminView('users/profile', array_merge($this->baseData('Author Profile', 'users'), [
            'subject_user' => $user,
            'profile'      => $profile,
        ]));
    }

    public function saveProfile(int $id)
    {
        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'display_name' => $this->request->getPost('display_name'),
            'bio'          => $this->request->getPost('bio'),
            'website'      => $this->request->getPost('website'),
            'twitter'      => ltrim($this->request->getPost('twitter') ?? '', '@'),
            'facebook'     => $this->request->getPost('facebook'),
            'linkedin'     => $this->request->getPost('linkedin'),
        ];

        // Handle avatar upload
        $avatar = $this->request->getFile('avatar');
        if ($avatar && $avatar->isValid() && ! $avatar->hasMoved()) {
            try {
                $mediaService  = new MediaService();
                $result        = $mediaService->upload($avatar, auth()->id());
                $data['avatar'] = $result['path'];
            } catch (\RuntimeException $e) {
                return redirect()->back()->with('error', 'Avatar upload failed: ' . $e->getMessage());
            }
        }

        $profileModel = new AuthorProfileModel();
        $profileModel->upsert($id, $data);

        return redirect()->to('/admin/users/' . $id . '/profile')->with('success', 'Profile saved.');
    }
}
