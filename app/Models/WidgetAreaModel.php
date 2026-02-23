<?php

namespace App\Models;

use CodeIgniter\Model;

class WidgetAreaModel extends Model
{
    protected $table      = 'widget_areas';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['name', 'slug', 'theme_id'];
}
