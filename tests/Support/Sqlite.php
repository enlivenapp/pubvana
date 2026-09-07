<?php

declare(strict_types=1);

namespace Pubvana\Tests\Support;

use PDO;

/**
 * SQLite in-memory database for DB-backed tests.
 *
 * ActiveRecord talks to whatever PDO it is handed, so an in-memory SQLite
 * connection works for model integration tests without touching the
 * MySQL-only migration runner. The schema is created directly here from
 * the same table shapes the migrations define.
 *
 * The connection is shared as a singleton so every model in a given test
 * run operates on the same in-memory database. Tests that need a clean
 * slate should recreate it via recreate() in setUp().
 */
final class Sqlite
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    /**
     * Get (creating on first use) the shared in-memory PDO connection
     * with its schema in place.
     */
    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::createSchema(self::$pdo);
        }

        return self::$pdo;
    }

    /**
     * Drop every table and recreate the schema, giving tests a clean
     * database. Existing table data is discarded.
     */
    public static function recreate(): PDO
    {
        $pdo = self::connection();

        self::$pdo->exec('PRAGMA foreign_keys = OFF');
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        }

        self::createSchema($pdo);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Build the schema for every core table. Mirrors the column shapes
     * defined in app/Database/Migrations/*.
     */
    private static function createSchema(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE settings (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                key         TEXT NOT NULL UNIQUE,
                value       TEXT,
                type        TEXT NOT NULL DEFAULT \'string\',
                autoload    INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE themes (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                name             TEXT NOT NULL,
                folder           TEXT NOT NULL,
                description      TEXT,
                version          TEXT,
                author           TEXT,
                screenshot       TEXT,
                is_active        INTEGER NOT NULL DEFAULT 0,
                disabled         INTEGER,
                disabled_reason  TEXT,
                installed_at     TEXT,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE theme_options (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                theme_id      INTEGER NOT NULL,
                option_key    TEXT NOT NULL,
                option_value  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE navigation (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                label       TEXT NOT NULL,
                url         TEXT NOT NULL,
                parent_id   INTEGER,
                sort_order  INTEGER NOT NULL DEFAULT 0,
                target      TEXT NOT NULL DEFAULT \'_self\',
                nav_group   TEXT NOT NULL DEFAULT \'primary\',
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE block_placements (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                region_id   TEXT NOT NULL,
                block_key   TEXT NOT NULL,
                sort_order  INTEGER NOT NULL DEFAULT 0,
                options     TEXT,
                created_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE mail_logs (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                to_address    TEXT NOT NULL,
                subject       TEXT NOT NULL,
                from_address  TEXT,
                transport     TEXT NOT NULL DEFAULT \'smtp\',
                status        TEXT NOT NULL DEFAULT \'sent\',
                error         TEXT,
                sent_at       TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE plugin_state (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                plugin_id   TEXT NOT NULL UNIQUE,
                enabled     INTEGER NOT NULL DEFAULT 0,
                priority    INTEGER NOT NULL DEFAULT 50,
                required    INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE broken_links (
                id                INTEGER PRIMARY KEY AUTOINCREMENT,
                source_type       TEXT NOT NULL,
                source_id         INTEGER NOT NULL,
                source_title      TEXT NOT NULL,
                url               TEXT NOT NULL,
                url_hash          TEXT NOT NULL,
                http_status       INTEGER,
                error_message     TEXT,
                dismissed         INTEGER NOT NULL DEFAULT 0,
                last_checked_at   TEXT,
                created_at        TEXT,
                updated_at        TEXT,
                UNIQUE (source_type, source_id, url_hash)
            )'
        );

        /*
         * Comments plugin tables. Column shapes mirror
         * plugins/Comments/Database/Migrations/2026-08-26-100002_CreateCommentsTable.php.
         */
        $pdo->exec(
            "CREATE TABLE comments (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                commentable_type TEXT NOT NULL,
                commentable_id  INTEGER NOT NULL,
                parent_id       INTEGER,
                user_id         INTEGER,
                guest_name      TEXT,
                guest_email     TEXT,
                guest_website   TEXT,
                body            TEXT NOT NULL,
                status          TEXT NOT NULL DEFAULT 'pending',
                ip_address      TEXT,
                created_at      TEXT,
                updated_at      TEXT
            )"
        );

        /*
         * Forms plugin tables. Column shapes mirror
         * plugins/Forms/Database/Migrations/2026-08-29-1000{01,02,03}_*.php.
         */
        $pdo->exec(
            'CREATE TABLE forms (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                name                TEXT NOT NULL,
                slug                TEXT NOT NULL UNIQUE,
                description         TEXT,
                status              TEXT NOT NULL DEFAULT \'draft\',
                submit_label        TEXT NOT NULL DEFAULT \'Submit\',
                success_message     TEXT,
                notification_emails TEXT,
                created_at          TEXT,
                updated_at          TEXT,
                deleted_at          TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE form_fields (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                form_id      INTEGER NOT NULL,
                type         TEXT NOT NULL,
                name         TEXT NOT NULL,
                label        TEXT NOT NULL,
                help_text    TEXT,
                placeholder  TEXT,
                is_required  INTEGER NOT NULL DEFAULT 0,
                width        TEXT NOT NULL DEFAULT \'full\',
                options_json TEXT,
                sort_order   INTEGER NOT NULL DEFAULT 1,
                created_at   TEXT,
                updated_at   TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE form_submissions (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                form_id      INTEGER NOT NULL,
                status       TEXT NOT NULL DEFAULT \'received\',
                ip_address   TEXT,
                user_agent   TEXT,
                referrer_url TEXT,
                payload_json TEXT,
                submitted_at TEXT,
                created_at   TEXT,
                updated_at   TEXT
            )'
        );

        /*
         * Shield tables. Column shapes mirror the vendor migrations in
         * vendor/enlivenapp/flight-shield/Database/Migrations/ so the auth
         * services and models behave under tests exactly as they do under
         * MySQL.
         */
        $pdo->exec(
            'CREATE TABLE users (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                username       TEXT UNIQUE,
                status         TEXT,
                status_message TEXT,
                active         INTEGER NOT NULL DEFAULT 0,
                last_active    TEXT,
                created_at     TEXT,
                updated_at     TEXT,
                deleted_at     TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_identities (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                type          TEXT NOT NULL,
                name          TEXT,
                secret        TEXT NOT NULL,
                secret2       TEXT,
                expires       TEXT,
                extra         TEXT,
                force_reset   INTEGER NOT NULL DEFAULT 0,
                last_used_at  TEXT,
                created_at    TEXT,
                updated_at    TEXT,
                UNIQUE (type, secret)
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_logins (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address  TEXT NOT NULL,
                user_agent  TEXT,
                id_type     TEXT NOT NULL,
                identifier  TEXT NOT NULL,
                user_id     INTEGER,
                date        TEXT NOT NULL,
                success     INTEGER NOT NULL DEFAULT 0
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_token_logins (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address  TEXT NOT NULL,
                user_agent  TEXT,
                id_type     TEXT NOT NULL,
                identifier  TEXT NOT NULL,
                user_id     INTEGER,
                date        TEXT NOT NULL,
                success     INTEGER NOT NULL DEFAULT 0
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_remember_tokens (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                selector         TEXT NOT NULL UNIQUE,
                hashed_validator TEXT NOT NULL,
                user_id          INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                expires          TEXT NOT NULL,
                created_at       TEXT,
                updated_at       TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_groups_users (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                group_alias TEXT NOT NULL,
                created_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_permissions_users (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                permission  TEXT NOT NULL,
                deny        INTEGER NOT NULL DEFAULT 0,
                created_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_groups (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                alias       TEXT NOT NULL,
                title       TEXT,
                description TEXT,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_permissions (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                alias       TEXT NOT NULL,
                description TEXT,
                created_at  TEXT,
                updated_at  TEXT
            )'
        );

        $pdo->exec(
            'CREATE TABLE auth_group_permissions (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id      INTEGER NOT NULL,
                permission_id INTEGER NOT NULL
            )'
        );
    }
}
