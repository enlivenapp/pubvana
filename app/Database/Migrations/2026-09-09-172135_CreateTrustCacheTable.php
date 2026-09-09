<?php

declare(strict_types=1);

namespace Pubvana\Database\Migrations;

use Enlivenapp\Migrations\Services\Migration;

/**
 * CreateTrustCacheTable - Local cache of trust service answers per addon.
 *
 * One row per installed addon (plugin or theme) keyed by the identity tuple
 * the Pubvana trust service at pubvanacms.com answers on. Rows are written by
 * TrustClientService after each successful check (cron batch, activation
 * gate, or manual recheck) and read by the admin plugins/themes pages and the
 * activation gate. There is no encryption at rest yet; that is planned for
 * the pre-beta security sweep.
 *
 * Version is part of the unique key, so an addon update is naturally a cache
 * miss and gets rechecked on the next batch.
 *
 * Schema:
 *   id         - Auto-increment primary key
 *   type       - 'plugin' or 'theme'
 *   slug       - Plugin folder/package name, or theme folder name
 *   version    - Addon version as reported in the manifest
 *   author     - Author the addon was reported under
 *   status     - 'trusted' | 'known' | 'malicious' | 'unknown'
 *   warning    - Optional warning text returned by the trust service
 *   checked_at - When this row was last confirmed by the trust service
 *   created_at - Row creation time
 *   updated_at - Last write time
 *
 * @package Pubvana\Database\Migrations
 */
class CreateTrustCacheTable extends Migration
{
    /**
     * Create the trust_cache table.
     *
     * UNIQUE constraint on (type, slug, version, author) guarantees one row
     * per addon identity. INDEX on checked_at serves the stale purge and
     * freshness reads.
     */
    public function change(): void
    {
        $this->table('trust_cache')
            ->addColumn('id', 'primary')
            ->addColumn('type', 'string', ['length' => 20])
            ->addColumn('slug', 'string', ['length' => 190])
            ->addColumn('version', 'string', ['length' => 50])
            ->addColumn('author', 'string', ['length' => 190])
            ->addColumn('status', 'string', ['length' => 20])
            ->addColumn('warning', 'text', ['nullable' => true])
            ->addColumn('checked_at', 'datetime')
            ->addColumn('created_at', 'datetime', ['nullable' => true])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['type', 'slug', 'version', 'author'], ['unique' => true])
            ->addIndex(['checked_at'])
            ->create();
    }
}
