<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemeModel extends Model
{
    protected $table      = 'themes';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'folder', 'is_active', 'version', 'installed_at'];
}
