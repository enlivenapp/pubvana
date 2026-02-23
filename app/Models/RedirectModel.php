<?php

namespace App\Models;

use CodeIgniter\Model;

class RedirectModel extends Model
{
    protected $table      = 'redirects';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['from_url', 'to_url', 'type'];
}
