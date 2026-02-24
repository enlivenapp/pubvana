<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use ZipArchive;

class PubvanaUpdate extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'pubvana:update';
    protected $description = 'Download and apply the latest Pubvana CMS release from GitHub.';
    protected $usage       = 'pubvana:update [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Show what would be done without modifying any files.',
    ];

    protected string $apiUrl = 'https://api.github.com/repos/enlivenapp/pubvana/releases/latest';

    /** Files / dirs inside app/ that must never be overwritten. */
    protected array $preservedFiles = [
        'Config/App.php',
        'Config/Database.php',
    ];

    /** Top-level directories to copy from the release ZIP. */
    protected array $copyDirs = ['app', 'public'];

    public function run(array $params): void
    {
        $dryRun = array_key_exists('dry-run', $params);

        if ($dryRun) {
            CLI::write('DRY RUN — no files will be modified.', 'yellow');
        }

        // ----------------------------------------------------------------
        // 1. Fetch release info
        // ----------------------------------------------------------------
        CLI::write('Checking GitHub for latest release...', 'cyan');

        $context  = stream_context_create(['http' => [
            'timeout' => 10,
            'header'  => "User-Agent: Pubvana-CMS/" . APP_VERSION . "\r\n"
                       . "Accept: application/vnd.github.v3+json\r\n",
        ]]);

        $json = @file_get_contents($this->apiUrl, false, $context);
        if ($json === false) {
            CLI::error('Could not reach GitHub API. Check your internet connection.');
            return;
        }

        $release = json_decode($json, true);
        if (empty($release['tag_name'])) {
            CLI::error('Unexpected response from GitHub.');
            return;
        }

        $latest  = ltrim($release['tag_name'], 'v');
        $zipUrl  = $release['zipball_url'] ?? '';

        CLI::write('Current version : ' . APP_VERSION, 'white');
        CLI::write('Latest version  : ' . $latest, 'white');

        // ----------------------------------------------------------------
        // 2. Version check
        // ----------------------------------------------------------------
        if (! version_compare($latest, APP_VERSION, '>')) {
            CLI::write('Pubvana is already up to date.', 'green');
            return;
        }

        CLI::write('');
        CLI::write("A new version ({$latest}) is available!", 'yellow');

        if (empty($zipUrl)) {
            CLI::error('No zipball URL in the GitHub response.');
            return;
        }

        // ----------------------------------------------------------------
        // 3. Confirm
        // ----------------------------------------------------------------
        if (! $dryRun) {
            $confirm = CLI::prompt('Apply this update now?', ['y', 'n']);
            if (strtolower($confirm) !== 'y') {
                CLI::write('Update cancelled.', 'yellow');
                return;
            }
        }

        // ----------------------------------------------------------------
        // 4. Download ZIP
        // ----------------------------------------------------------------
        $updateDir = WRITEPATH . 'updates/';
        $zipPath   = $updateDir . 'pubvana-' . $latest . '.zip';

        if (! $dryRun) {
            if (! is_dir($updateDir)) {
                mkdir($updateDir, 0755, true);
            }

            CLI::write('Downloading ' . $zipUrl . ' ...', 'cyan');
            $zipData = @file_get_contents($zipUrl, false, $context);
            if ($zipData === false) {
                CLI::error('Failed to download the release ZIP.');
                return;
            }
            file_put_contents($zipPath, $zipData);
            CLI::write('Downloaded to ' . $zipPath, 'white');
        } else {
            CLI::write('[dry-run] Would download ZIP to ' . $zipPath, 'yellow');
        }

        // ----------------------------------------------------------------
        // 5. Extract ZIP
        // ----------------------------------------------------------------
        $extractDir = $updateDir . 'pubvana-' . $latest . '/';

        if (! $dryRun) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                CLI::error('Failed to open ZIP archive.');
                return;
            }

            // Validate paths (no directory traversal)
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_contains($name, '..')) {
                    CLI::error('ZIP contains unsafe path: ' . $name);
                    $zip->close();
                    return;
                }
            }

            $zip->extractTo($extractDir);
            $zip->close();

            // Detect inner wrapper directory (GitHub wraps: enlivenapp-pubvana-{hash}/)
            $entries  = array_diff(scandir($extractDir), ['.', '..']);
            $innerDir = $extractDir;
            if (count($entries) === 1) {
                $first = reset($entries);
                if (is_dir($extractDir . $first)) {
                    $innerDir = $extractDir . $first . '/';
                }
            }

            CLI::write('Extracted to ' . $innerDir, 'white');
        } else {
            $innerDir = $extractDir . 'enlivenapp-pubvana-xxxx/';
            CLI::write('[dry-run] Would extract to ' . $innerDir, 'yellow');
        }

        // ----------------------------------------------------------------
        // 6. Copy app/ and public/ (preserving config files)
        // ----------------------------------------------------------------
        foreach ($this->copyDirs as $dir) {
            $src  = $innerDir . $dir . '/';
            $dest = ROOTPATH  . $dir . '/';

            if (! $dryRun) {
                if (! is_dir($src)) {
                    CLI::write("  Skipping {$dir}/ — not found in release.", 'yellow');
                    continue;
                }
                $this->copyDirectory($src, $dest, $dryRun);
            } else {
                CLI::write("[dry-run] Would copy {$src} → {$dest}", 'yellow');
            }
        }

        // ----------------------------------------------------------------
        // 7. Update APP_VERSION in Constants.php
        // ----------------------------------------------------------------
        $constantsPath = APPPATH . 'Config/Constants.php';
        if (! $dryRun) {
            $src = file_get_contents($constantsPath);
            $new = preg_replace(
                "/define\('APP_VERSION',\s*'[^']+'\)/",
                "define('APP_VERSION', '{$latest}')",
                $src
            );
            if ($new && $new !== $src) {
                file_put_contents($constantsPath, $new);
                CLI::write('Updated APP_VERSION to ' . $latest . ' in Constants.php', 'white');
            }
        } else {
            CLI::write("[dry-run] Would update APP_VERSION → {$latest} in Constants.php", 'yellow');
        }

        // ----------------------------------------------------------------
        // 8. Run migrations
        // ----------------------------------------------------------------
        if (! $dryRun) {
            CLI::write('Running migrations...', 'cyan');
            $this->call('migrate', ['--all']);
        } else {
            CLI::write('[dry-run] Would run: php spark migrate --all', 'yellow');
        }

        // ----------------------------------------------------------------
        // 9. Clear cache
        // ----------------------------------------------------------------
        if (! $dryRun) {
            cache()->clean();
            cache()->delete('pubvana_update_check');
            CLI::write('Cache cleared.', 'white');
        } else {
            CLI::write('[dry-run] Would clear application cache.', 'yellow');
        }

        // ----------------------------------------------------------------
        // 10. Cleanup
        // ----------------------------------------------------------------
        if (! $dryRun) {
            @unlink($zipPath);
            $this->removeDirectory($extractDir);
            CLI::write('Cleaned up temporary files.', 'white');
        } else {
            CLI::write('[dry-run] Would clean up ' . $zipPath, 'yellow');
        }

        CLI::newLine();
        if ($dryRun) {
            CLI::write('Dry run complete. Re-run without --dry-run to apply the update.', 'yellow');
        } else {
            CLI::write('Pubvana updated to ' . $latest . ' successfully!', 'green');
        }
    }

    /**
     * Recursively copy a directory, skipping preserved config files.
     */
    protected function copyDirectory(string $src, string $dest, bool $dryRun): void
    {
        if (! is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $items = array_diff(scandir($src), ['.', '..']);

        foreach ($items as $item) {
            $srcPath  = $src  . $item;
            $destPath = $dest . $item;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath . '/', $destPath . '/', $dryRun);
            } else {
                // Check preserved files (relative to app/)
                $relPath = str_replace(APPPATH, '', $destPath);
                foreach ($this->preservedFiles as $preserved) {
                    if (str_ends_with($destPath, str_replace('/', DIRECTORY_SEPARATOR, $preserved))) {
                        CLI::write('  Preserving ' . $destPath, 'cyan');
                        continue 2;
                    }
                }

                copy($srcPath, $destPath);
            }
        }
    }

    /**
     * Recursively remove a directory.
     */
    protected function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . $item;
            is_dir($path) ? $this->removeDirectory($path . '/') : @unlink($path);
        }

        @rmdir($dir);
    }
}
