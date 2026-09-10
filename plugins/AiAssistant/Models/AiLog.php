<?php

declare(strict_types=1);

namespace Pubvana\Plugins\AiAssistant\Models;

/**
 * AiLog - AI Assistant audit entry (ai_logs table).
 *
 * One row per request to the /api/ai/* API, including authentication
 * failures and denied (ungranted) attempts. Key identity is snapshotted
 * by name so the audit trail survives key deletion.
 *
 * Schema:
 *   id          - Auto-increment primary key
 *   key_id      - FK to ai_keys.id (nullable on failures)
 *   key_name    - Snapshot of the key name (survives key deletion)
 *   method      - HTTP method (GET/POST)
 *   endpoint    - Request path under /api/ai/*
 *   entity_type - Entity acted on (post, page, comment, redirect, ...)
 *   entity_id   - Target entity id when applicable
 *   outcome     - 'ok', 'denied', or 'error'
 *   detail      - Human detail (denied permission, error message, ...)
 *   ip          - Client IP address
 *   created_at  - Request timestamp
 *
 * @package Pubvana\Plugins\AiAssistant\Models
 *
 * @property int         $id
 * @property int|null    $key_id
 * @property string|null $key_name
 * @property string      $method
 * @property string      $endpoint
 * @property string|null $entity_type
 * @property int|null    $entity_id
 * @property string      $outcome      ok|denied|error
 * @property string|null $detail
 * @property string|null $ip
 * @property string|null $created_at
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 * @method self like(string $field, mixed $value, string $operator = 'AND')
 * @method self isNull(string $field, string $operator = 'AND')
 * @method self order(string $field)
 * @method self select(string $field, string ...$fields)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 */
class AiLog extends \Pubvana\Models\AbstractModel
{
    /**
     * @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo
     * @param array<string, mixed>                                 $config
     */
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'ai_logs', $config);
    }

    /**
     * @param int $limit
     * @return self[] Most recent log entries, newest first.
     */
    public function recent(int $limit = 200): array
    {
        $limit = max(1, $limit);
        $model = new self($this->getDatabaseConnection());
        return $model->order('id DESC')->limit($limit)->findAll();
    }
}