<?php

namespace App\Commands;

use App\Services\WordPressImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WpImport extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'wp:import';
    protected $description = 'Import a WordPress WXR export file into Pubvana.';
    protected $usage       = 'wp:import <path/to/export.xml> [--dry-run]';
    protected $arguments   = [
        'file' => 'Path to the WordPress WXR export (.xml) file.',
    ];
    protected $options = [
        '--dry-run' => 'Preview what would be imported without writing to the database.',
    ];

    public function run(array $params): void
    {
        $path   = $params[0] ?? CLI::prompt('Path to WXR file');
        $dryRun = array_key_exists('dry-run', $params);

        if (! file_exists($path)) {
            CLI::error('File not found: ' . $path);
            return;
        }

        if ($dryRun) {
            CLI::write('DRY RUN — no data will be written.', 'yellow');
        }

        CLI::write('Starting WordPress import...', 'green');

        $service = new WordPressImportService();
        $service->setDryRun($dryRun);
        $results = $service->import($path);

        CLI::newLine();
        CLI::write('Results:', 'cyan');
        foreach (['authors', 'categories', 'tags', 'posts', 'pages', 'comments'] as $type) {
            CLI::write(sprintf(
                '  %-12s created: %d  skipped: %d',
                ucfirst($type),
                $results[$type]['created'],
                $results[$type]['skipped']
            ), 'white');
        }

        if (! empty($results['errors'])) {
            CLI::newLine();
            CLI::write('Errors:', 'red');
            foreach ($results['errors'] as $err) {
                CLI::error('  ' . $err);
            }
        }

        CLI::newLine();
        if ($dryRun) {
            CLI::write('Dry run complete. Re-run without --dry-run to import.', 'yellow');
        } else {
            CLI::write('Import complete!', 'green');
        }
    }
}
