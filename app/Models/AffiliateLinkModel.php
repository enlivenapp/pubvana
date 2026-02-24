<?php

namespace App\Models;

use CodeIgniter\Model;

class AffiliateLinkModel extends Model
{
    protected $table         = 'affiliate_links';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name', 'slug', 'destination_url', 'is_active',
    ];

    protected $validationRules = [
        'name'            => 'required|max_length[150]',
        'slug'            => 'required|max_length[100]|alpha_dash',
        'destination_url' => 'required|valid_url_strict',
        'is_active'       => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Return all links with their click counts, newest first.
     */
    public function withClickCounts(): array
    {
        return $this->db->table('affiliate_links al')
            ->select('al.*, COUNT(ac.id) AS click_count')
            ->join('affiliate_clicks ac', 'ac.link_id = al.id', 'left')
            ->groupBy('al.id')
            ->orderBy('al.created_at', 'DESC')
            ->get()->getResultObject();
    }

    /**
     * Find an active link by slug.
     */
    public function findActiveBySlug(string $slug): ?object
    {
        return $this->where('slug', $slug)->where('is_active', 1)->first();
    }
}
