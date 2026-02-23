<?php

namespace App\Models;

use CodeIgniter\Model;

class SocialModel extends Model
{
    protected $table      = 'social';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['platform', 'url', 'icon', 'sort_order', 'is_active'];
}
