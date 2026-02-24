<?php

namespace App\Commands;

use App\Services\MarketplaceService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LicenseRevalidate extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'marketplace:revalidate';
    protected $description = 'Re-validate installed premium item licenses against pubvana.net.';
    protected $usage       = 'marketplace:revalidate [--force]';
    protected $options     = [
        '--force' => 'Re-validate all licensed items, not just overdue ones.',
    ];

    public function run(array $params): void
    {
        // Dev domain check — mirror MarketplaceService::isDevDomain()
        $host = strtolower(parse_url(base_url(), PHP_URL_HOST) ?? '');
        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            CLI::write('Dev domain detected — license checks skipped.', 'yellow');
            return;
        }

        $force = array_key_exists('force', $params) || CLI::getOption('force') !== null;

        CLI::write('Running license re-validation' . ($force ? ' (force mode)' : '') . '...', 'white');

        $service = new MarketplaceService();
        $results = $service->revalidateLicenses($force);

        if (empty($results)) {
            CLI::write('No licensed items found.', 'yellow');
        }

        $hasInvalid = false;

        foreach ($results as $item) {
            switch ($item['status']) {
                case 'valid':
                    CLI::write('  [OK]          ' . $item['slug'], 'green');
                    break;
                case 'invalid':
                    CLI::write('  [INVALID]     ' . $item['slug'], 'red');
                    $hasInvalid = true;
                    break;
                case 'unreachable':
                    CLI::write('  [UNREACHABLE] ' . $item['slug'], 'yellow');
                    break;
                case 'skipped':
                    CLI::write('  [SKIPPED]     ' . $item['slug'], 'dark_gray');
                    break;
                default:
                    CLI::write('  [UNKNOWN]     ' . $item['slug'], 'white');
            }
        }

        // Clear the daily cache so the next web request reflects fresh data
        cache()->delete('license_due_check');

        if ($hasInvalid) {
            CLI::write('');
            CLI::write('WARNING: One or more licenses are invalid. Affected themes cannot be activated.', 'red');
        } else {
            CLI::write('');
            CLI::write('Done.', 'green');
        }
    }
}
