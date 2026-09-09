<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * TrustCache - ActiveRecord model for the trust_cache table.
 *
 * One row per installed addon (plugin or theme) keyed by the identity tuple
 * (type, slug, version, author) that the Pubvana trust service answers on.
 * Written by TrustClientService after each successful check and read by the
 * admin plugins/themes pages and the activation gate.
 *
 * Version is part of the unique key, so an addon update is naturally a cache
 * miss and gets rechecked on the next batch.
 *
 * @method self eq(string $field, mixed $value, string $operator = 'AND')
 *
 * @package Pubvana\Models
 */
class TrustCache extends AbstractModel
{
    public const STATUS_TRUSTED   = 'trusted';
    public const STATUS_KNOWN     = 'known';
    public const STATUS_MALICIOUS = 'malicious';
    public const STATUS_UNKNOWN   = 'unknown';

    /** @param \flight\database\DatabaseInterface|\PDO|\mysqli|null $pdo */
    public function __construct($pdo = null)
    {
        parent::__construct($pdo, 'trust_cache');
    }

    public int $id;
    public string $type;
    public string $slug;
    public string $version;
    public string $author;
    public string $status;
    public ?string $warning = null;
    public string $checked_at;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * The composite key format used for bulk status maps.
     *
     * @param string $type    'plugin' or 'theme'
     * @param string $slug    Addon slug (folder or package name)
     * @param string $version Addon version
     * @param string $author  Author as reported to the trust service
     */
    public static function compositeKey(string $type, string $slug, string $version, string $author): string
    {
        return $type . '|' . $slug . '|' . $version . '|' . $author;
    }

    /**
     * Find one cache row by its full addon identity.
     */
    public function findByAddon(string $type, string $slug, string $version, string $author): ?self
    {
        $model = new self($this->getDatabaseConnection());
        $model->eq('type', $type)
            ->eq('slug', $slug)
            ->eq('version', $version)
            ->eq('author', $author)
            ->find();

        return $model->isHydrated() ? $model : null;
    }

    /**
     * Insert or update the cache row for an addon identity, stamping
     * checked_at with the current time.
     *
     * @param string  $type    'plugin' or 'theme'
     * @param string  $slug    Addon slug (folder or package name)
     * @param string  $version Addon version
     * @param string  $author  Author as reported to the trust service
     * @param string  $status  'trusted' | 'known' | 'malicious' | 'unknown'
     * @param ?string $warning Warning text from the trust service
     */
    public function upsert(string $type, string $slug, string $version, string $author, string $status, ?string $warning): self
    {
        $now = date('Y-m-d H:i:s');
        $row = $this->findByAddon($type, $slug, $version, $author);

        if ($row === null) {
            $row = new self($this->getDatabaseConnection());
            $row->type = $type;
            $row->slug = $slug;
            $row->version = $version;
            $row->author = $author;
            $row->status = $status;
            $row->warning = $warning;
            $row->checked_at = $now;
            $row->created_at = $now;
            $row->updated_at = $now;
            $row->insert();

            return $row;
        }

        $row->status = $status;
        $row->warning = $warning;
        $row->checked_at = $now;
        $row->updated_at = $now;
        $row->save();

        return $row;
    }

    /**
     * Load every cache row in one query, keyed by composite addon identity.
     *
     * @return array<string, array{status: string, warning: ?string, checked_at: string}>
     */
    public function statusesForAll(): array
    {
        $rows = (new self($this->getDatabaseConnection()))->findAll();
        $map = [];
        foreach ($rows as $row) {
            $map[self::compositeKey($row->type, $row->slug, $row->version, $row->author)] = [
                'status'     => (string) $row->status,
                'warning'    => $row->warning,
                'checked_at' => (string) $row->checked_at,
            ];
        }
        return $map;
    }

    /**
     * Delete cache rows not confirmed within the given window.
     *
     * Trusted rows are kept: they are deliberately never rechecked, so their
     * cache entries are the only record that the answer is already in hand.
     * Everything else is safe to drop, the next batch rechecks it.
     *
     * @param int $hours Age in hours past which a row is stale
     * @return int Rows deleted
     */
    public function purgeStale(int $hours): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));
        $connection = $this->getDatabaseConnection();

        try {
            $statement = $connection->prepare(
                'DELETE FROM trust_cache WHERE checked_at < ? AND status <> ?'
            );
            $statement->execute([$cutoff, self::STATUS_TRUSTED]);
            return $statement->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }
}
