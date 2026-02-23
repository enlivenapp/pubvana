<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table      = 'media';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['filename', 'path', 'mime_type', 'size', 'alt_text', 'title', 'uploaded_by'];
}
