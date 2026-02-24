<?php

namespace App\Models;

use CodeIgniter\Model;

class AffiliateClickModel extends Model
{
    protected $table         = 'affiliate_clicks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'link_id', 'ip_hash', 'referrer', 'created_at',
    ];

    /**
     * Return paginated clicks for a specific link, newest first.
     */
    public function getForLink(int $linkId, int $perPage = 25): array
    {
        return $this->where('link_id', $linkId)
                    ->orderBy('id', 'DESC')
                    ->paginate($perPage);
    }

    /**
     * Total click count for a link.
     */
    public function countForLink(int $linkId): int
    {
        return $this->where('link_id', $linkId)->countAllResults();
    }
}
