<?php

namespace App\Models;

use CodeIgniter\Model;

class PageViewModel extends Model
{
    protected $table      = 'page_views';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useTimestamps  = false;
    protected $allowedFields  = ['entity_type', 'entity_id', 'referrer_domain', 'viewed_at'];

    // -------------------------------------------------------------------------

    /**
     * Total view events in the given period.
     */
    public function totalViews(int $days): int
    {
        return (int) $this->db->table('page_views')
            ->where('viewed_at >=', $this->cutoff($days))
            ->countAllResults();
    }

    /**
     * Views grouped by day for the period, ordered oldest → newest.
     * Returns objects with ->date (Y-m-d) and ->view_count.
     */
    public function getViewsByDay(int $days): array
    {
        return $this->db->table('page_views')
            ->select('DATE(viewed_at) AS date, COUNT(*) AS view_count')
            ->where('viewed_at >=', $this->cutoff($days))
            ->groupBy('DATE(viewed_at)')
            ->orderBy('date', 'ASC')
            ->get()->getResultObject();
    }

    /**
     * Top posts by view count in the period.
     * Returns objects with ->entity_id, ->title, ->slug, ->view_count.
     */
    public function getTopPosts(int $days, int $limit = 10): array
    {
        return $this->db->table('page_views pv')
            ->select('pv.entity_id, p.title, p.slug, COUNT(*) AS view_count')
            ->join('posts p', 'p.id = pv.entity_id AND p.deleted_at IS NULL')
            ->where('pv.entity_type', 'post')
            ->where('pv.viewed_at >=', $this->cutoff($days))
            ->groupBy('pv.entity_id, p.title, p.slug')
            ->orderBy('view_count', 'DESC')
            ->limit($limit)
            ->get()->getResultObject();
    }

    /**
     * Top referrer domains in the period.
     * Returns objects with ->referrer_domain and ->view_count.
     */
    public function getReferrers(int $days, int $limit = 10): array
    {
        return $this->db->table('page_views')
            ->select('referrer_domain, COUNT(*) AS view_count')
            ->where('viewed_at >=', $this->cutoff($days))
            ->where('referrer_domain IS NOT NULL', null, false)
            ->groupBy('referrer_domain')
            ->orderBy('view_count', 'DESC')
            ->limit($limit)
            ->get()->getResultObject();
    }

    // -------------------------------------------------------------------------

    private function cutoff(int $days): string
    {
        return date('Y-m-d H:i:s', strtotime("-{$days} days"));
    }
}
