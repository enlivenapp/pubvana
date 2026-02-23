<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table      = 'categories';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'slug', 'description', 'parent_id'];

    public function findBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->first();
    }

    public function getWithPostCount(): array
    {
        return $this->db->table('categories c')
            ->select('c.*, COUNT(ptc.post_id) as post_count')
            ->join('posts_to_categories ptc', 'ptc.category_id = c.id', 'left')
            ->join('posts p', 'p.id = ptc.post_id AND p.status = "published" AND p.deleted_at IS NULL', 'left')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->get()->getResultObject();
    }
}
