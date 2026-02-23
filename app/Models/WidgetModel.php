<?php

namespace App\Models;

use CodeIgniter\Model;

class WidgetModel extends Model
{
    protected $table      = 'widgets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'folder', 'description', 'version', 'is_active'];
}
