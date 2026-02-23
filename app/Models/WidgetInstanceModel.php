<?php

namespace App\Models;

use CodeIgniter\Model;

class WidgetInstanceModel extends Model
{
    protected $table      = 'widget_instances';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['widget_id', 'widget_area_id', 'sort_order', 'options_json'];
}
