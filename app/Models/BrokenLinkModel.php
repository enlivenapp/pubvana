<?php

namespace App\Models;

use CodeIgniter\Model;

class BrokenLinkModel extends Model
{
    protected $table         = 'broken_link_results';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'source_type', 'source_id', 'source_title', 'url', 'url_hash',
        'http_status', 'error_message', 'dismissed', 'last_checked_at',
    ];

    /**
     * Return all non-dismissed results grouped by source, broken first.
     * Returns an array of rows ordered by source_type, source_id, url.
     */
    public function getResults(bool $showDismissed = false): array
    {
        $builder = $this->db->table('broken_link_results')
            ->orderBy('source_type', 'ASC')
            ->orderBy('source_id',   'ASC')
            ->orderBy('url',         'ASC');

        if (! $showDismissed) {
            $builder->where('dismissed', 0);
        }

        return $builder->get()->getResultObject();
    }

    /**
     * Upsert a result row keyed on (source_type, source_id, url_hash).
     */
    public function upsert(array $data): void
    {
        $now  = date('Y-m-d H:i:s');
        $hash = sha1($data['url']);

        $existing = $this->db->table('broken_link_results')
            ->where('source_type', $data['source_type'])
            ->where('source_id',   $data['source_id'])
            ->where('url_hash',    $hash)
            ->get()->getRowObject();

        $row = [
            'source_type'     => $data['source_type'],
            'source_id'       => $data['source_id'],
            'source_title'    => mb_substr($data['source_title'] ?? '', 0, 255),
            'url'             => $data['url'],
            'url_hash'        => $hash,
            'http_status'     => $data['http_status'],
            'error_message'   => isset($data['error_message']) ? mb_substr($data['error_message'], 0, 255) : null,
            'last_checked_at' => $now,
            'updated_at'      => $now,
        ];

        if ($existing) {
            // Reset dismissed flag if the link is still broken
            if (! $this->isOk($data['http_status'])) {
                $row['dismissed'] = 0;
            }
            $this->db->table('broken_link_results')
                ->where('id', $existing->id)
                ->update($row);
        } else {
            $row['dismissed']  = 0;
            $row['created_at'] = $now;
            $this->db->table('broken_link_results')->insert($row);
        }
    }

    /**
     * Remove results for URLs that are now OK (so old stale records don't linger).
     */
    public function deleteOk(string $sourceType, int $sourceId): void
    {
        // Called after a full re-scan of a single source — remove any rows
        // whose status is now 2xx (they were fixed).
        $this->db->table('broken_link_results')
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('http_status >=', 200)
            ->where('http_status <', 300)
            ->delete();
    }

    public function isOk(?int $status): bool
    {
        return $status !== null && $status >= 200 && $status < 300;
    }
}
