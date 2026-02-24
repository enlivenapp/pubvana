<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'username', 'action', 'subject_type',
        'subject_id', 'description', 'ip_address', 'created_at',
    ];

    /**
     * Return paginated log entries, optionally filtered by subject_type.
     *
     * @param  int    $perPage
     * @param  string $type    subject_type filter (empty = all)
     * @return array
     */
    public function getRecent(int $perPage = 20, string $type = ''): array
    {
        $builder = $this->orderBy('id', 'DESC');

        if ($type !== '') {
            $builder = $builder->where('subject_type', $type);
        }

        return $builder->paginate($perPage);
    }
}
