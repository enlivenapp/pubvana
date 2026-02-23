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

        // Protect site owner (lowest ID)
        $ownerId = (int) db_connect()->table('users')->selectMin('id')->get()->getRowObject()->id;
        if ($id === $ownerId && auth()->id() !== $ownerId) {
            return redirect()->to('/admin/users')->with('error', 'The site owner account cannot be modified.');
        }

        $userModel = new UserModel();
        $user      = $userModel->findById($id);
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Role
        $role = $this->request->getPost('role');
        if ($role && array_key_exists($role, config('AuthGroups')->groups)) {
            foreach ($user->getGroups() as $g) {
                $user->removeGroup($g);
            }
            $user->addGroup($role);
        }

        // Active status — use Shield ban to actually block login, keep active col in sync
        $db = db_connect();
        if ($this->request->getPost('active')) {
            $user->unBan();
            $db->table('users')->where('id', $id)->update(['active' => 1]);
        } elseif ($id !== auth()->id() && $id !== $ownerId) {
            $user->ban('Deactivated by admin');
            $db->table('users')->where('id', $id)->update(['active' => 0]);
            $db->table('auth_remember_tokens')->where('user_id', $id)->delete();
        }

        // Password (optional)
        $password = $this->request->getPost('password');
        if ($password) {
            $user->fill(['password' => $password]);
            $userModel->save($user);
        }

        return redirect()->to('/admin/users')->with('success', 'User updated.');
    }

    public function delete(int $id)
    {
        if ($id === auth()->id()) {
            return redirect()->to('/admin/users')->with('error', 'Cannot delete yourself.');
        }
        $ownerId = (int) db_connect()->table('users')->selectMin('id')->get()->getRowObject()->id;
        if ($id === $ownerId) {
            return redirect()->to('/admin/users')->with('error', 'The site owner account cannot be deleted.');
        }
        (new UserModel())->delete($id, true);
        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    public function create(): string
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('users/create', $this->baseData('Create User', 'users'));
    }

    public function store()
    {
        if (! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        if (! $this->validate([
            'username' => 'required|min_length[3]|max_length[30]|is_unique[users.username]',
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[8]',
            'role'     => 'required|in_list[subscriber,author,editor,admin,superadmin]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $users = auth()->getProvider();
        $user  = new \CodeIgniter\Shield\Entities\User([
            'username' => $this->request->getPost('username'),
            'active'   => 1,
        ]);
        $user->setEmail($this->request->getPost('email'));
        $user->setPassword($this->request->getPost('password'));
        $users->save($user);

        $newUser = $users->findByCredentials(['email' => $this->request->getPost('email')]);
        if ($newUser) {
            $newUser->addGroup($this->request->getPost('role'));
        }

        return redirect()->to('/admin/users')->with('success', 'User created.');
    }

    public function profile(int $id): string
    {
        if ($id !== auth()->id() && ! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

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
        if ($id !== auth()->id() && ! auth()->user()->can('users.manage')) {
            return redirect()->to('/admin/users/' . $id . '/profile')->with('error', 'Permission denied.');
        }

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
