<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthorProfileModel extends Model
{
    protected $table            = 'author_profiles';
    protected $primaryKey       = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id', 'display_name', 'bio', 'avatar',
        'website', 'twitter', 'facebook', 'linkedin',
    ];

    public function getByUserId(int $userId): ?object
    {
        return $this->where('user_id', $userId)->first();
    }

    public function upsert(int $userId, array $data): void
    {
        $existing = $this->getByUserId($userId);
        if ($existing) {
            $this->where('user_id', $userId)->set($data)->update();
        } else {
            $data['user_id'] = $userId;
            $this->insert($data);
        }
    }
}
