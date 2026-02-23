<?php

/**
 * Pubvana test bootstrap.
 *
 * Extends CI4's default test bootstrap with a one-time database migration step
 * so each test class doesn't need to run migrations individually.
 *
 * Migrations run once before the whole suite, then tests only truncate + seed.
 */

// Load CI4's standard test bootstrap first
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

// Only set up the database if we're using a real DB (ENVIRONMENT = testing)
if (ENVIRONMENT === 'testing') {
    _pubvana_setup_test_db();
}

/**
 * Run App + Shield migrations on the test database exactly once.
 * Skips gracefully if tables already exist (idempotent migrations).
 */
function _pubvana_setup_test_db(): void
{
    try {
        $db = db_connect('tests');
        $db->initialize();

        $config          = new \Config\Migrations();
        $config->enabled = true;

        $runner = new \CodeIgniter\Database\MigrationRunner($config, $db);
        $runner->setSilent(true);

        // Run App migrations (createTable uses IF NOT EXISTS)
        $runner->setNamespace('App');
        $runner->latest('tests');

        // Run Shield migrations (creates auth tables)
        $runner->setNamespace('CodeIgniter\\Shield');
        $runner->latest('tests');

    } catch (\Throwable $e) {
        // Migrations may already be up-to-date — swallow non-fatal errors
        // (e.g. duplicate table from partial prior run)
    }
}
