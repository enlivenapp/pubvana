<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = \CodeIgniter\Shield\Models\UserModel::class;
        $userModel = new $users();
        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => 'superadmin',
        ]);
        $userModel->save($user);
        $user = $userModel->findById($userModel->getInsertID());
        $user->createEmailIdentity([
            'email'    => 'admin@example.com',
            'password' => 'Admin@12345',
        ]);
        $user->addGroup('superadmin');
    }
}
