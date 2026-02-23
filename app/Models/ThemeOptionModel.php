<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemeOptionModel extends Model
{
    protected $table      = 'theme_options';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['theme_id', 'option_key', 'option_value'];
}
