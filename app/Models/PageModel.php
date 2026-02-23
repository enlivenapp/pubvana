<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table      = 'pages';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'title', 'slug', 'content', 'content_type', 'status', 'parent_id',
        'sort_order', 'meta_title', 'meta_description', 'is_system',
    ];

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function published(): static
    {
        return $this->where('status', 'published');
    }
}
