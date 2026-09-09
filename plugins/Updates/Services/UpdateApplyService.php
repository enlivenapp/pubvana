<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\Services;

use flight\Engine;
use Pubvana\Models\TrustCache;
use Pubvana\Plugins\Backups\Services\ProgressReporter as BackupProgressReporter;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Applies a Pubvana release: backup, download, validate, extract, copy,
 * migrate, clean up.
 *
 * The pre-update backup is a hard requirement: it is the rollback path,
 * and a backup failure aborts the update before anything is touched.
 * Rollback itself stays in the Backups plugin (restore from snapshot).
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
final class UpdateApplyService
{
    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    private UpdateService $updates;

    /**
     * @param Engine<object>       $app
     * @param array<string, mixed> $config
     */
    public function __construct(Engine $app, array $config = [])
    {
        $this->app     = $app;
        $this->config  = $config;
        $this->updates = new UpdateService($app, $config);
    }

    /**
     * Apply the named release.
     *
     * @param  string   $targetVersion  Release version to move to
     * @param  string   $triggeredBy    Username or 'cron'/'cli' for attribution
     * @param  bool     $ignoreBreaking Manual runs may cross breaking changes
     *         after seeing them; automatic runs never set this
     * @param  callable|null $onProgress Optional console echo,
     *         fn(string $label, string $detail): void
     * @return bool True on success; on failure the progress file holds the error
     */
    public function apply(string $targetVersion, string $triggeredBy, bool $ignoreBreaking = false, ?callable $onProgress = null): bool
    {
        $reporter = new UpdateProgress($this->storageDir());

        if (!$reporter->acquireLock()) {
            $reporter->error('An update is already in progress.');
            return false;
        }

        $refusal = $this->trustRefusal($targetVersion, $ignoreBreaking);
        if ($refusal !== null) {
            $reporter->error($refusal);
            return false;
        }

        if ($onProgress !== null) {
            $reporter->onWrite($onProgress);
        }

        try {
            $reporter->start(self::phases());

            $warnings        = $this->runPreflight($reporter, $targetVersion, $ignoreBreaking);
            $backupFile      = $this->runBackup($reporter, $triggeredBy);
            $zipPath         = $this->runDownload($reporter, $targetVersion);
            $source          = $this->runExtract($reporter, $zipPath);
            $this->runCopy($reporter, $source);
            $migrationsError = $this->runMigrations($reporter);
            $newVersion      = $this->runCleanup($reporter, $zipPath);

            $reporter->complete([
                'previous_version' => $this->updates->currentVersion(),
                'new_version'      => $newVersion,
                'target_version'   => $targetVersion,
                'backup_file'      => basename($backupFile),
                'migrations_error' => $migrationsError,
                'warnings'         => $warnings,
                'finished_at'      => date('c'),
                'triggered_by'     => $triggeredBy,
            ]);

            return true;
        } catch (Throwable $e) {
            $reporter->error($e->getMessage());

            return false;
        } finally {
            $reporter->releaseLock();
        }
    }

    /**
     * The trust verdict for a release, same posture as the activation gate.
     * Malicious is always refused. Un-evaluated releases block automatic
     * runs only: a manual run is itself the confirmation, and the web apply
     * carries its own modal gate before reaching this service.
     *
     * The cached answer is preferred. The web gate stores it seconds before
     * the run starts (or hours before, for the auto-update cron); only a
     * cache miss asks the trust service live. A trust layer outage never
     * blocks an update: fail closed is for verdicts, not for reachability.
     */
    private function trustRefusal(string $targetVersion, bool $manual): ?string
    {
        try {
            $client = $this->app->trustClient();
            $item   = $client->coreItem($targetVersion);
        } catch (Throwable) {
            return null;
        }

        try {
            $status  = null;
            $warning = null;
            $cached  = $client->getCachedStatus($item['type'], $item['slug'], $item['version'], $item['author']);
            if ($cached !== null) {
                $status  = $cached['status'];
                $warning = $cached['warning'];
            } else {
                $answer  = $client->checkAddon($item['type'], $item['slug'], $item['version'], $item['author'], $item['origin']);
                $status  = $answer['status'];
                $warning = $answer['warning'];
            }
        } catch (Throwable) {
            return null;
        }

        if ($status === TrustCache::STATUS_MALICIOUS) {
            return 'The Pubvana trust service found this release to be malicious'
                . ($warning !== null && $warning !== '' ? ': ' . $warning : '.');
        }
        if ($status === TrustCache::STATUS_UNKNOWN && !$manual) {
            return 'The Pubvana trust service has not evaluated this release yet, so an automatic update cannot apply it.';
        }

        return null;
    }

    /**
     * The fixed phase checklist for one update run.
     *
     * @return list<array{name: string, label: string}>
     */
    public static function phases(): array
    {
        return [
            ['name' => 'preflight', 'label' => 'Running preflight checks'],
            ['name' => 'backup',    'label' => 'Creating pre-update backup'],
            ['name' => 'download',  'label' => 'Downloading update'],
            ['name' => 'validate',  'label' => 'Validating downloaded zip'],
            ['name' => 'extract',   'label' => 'Extracting release'],
            ['name' => 'copy',      'label' => 'Copying release files'],
            ['name' => 'migrate',   'label' => 'Running database migrations'],
            ['name' => 'cleanup',   'label' => 'Cleaning up'],
        ];
    }

    // ------------------------------------------------------------------
    // Phases
    // ------------------------------------------------------------------

    /**
     * Run the preflight phase. Only Required (hard) failures abort; an
     * Optional failure is collected as a warning and carried into the
     * run result.
     *
     * @return list<string> Warnings for non-blocking failures
     */
    private function runPreflight(UpdateProgress $reporter, string $targetVersion, bool $ignoreBreaking): array
    {
        $reporter->beginPhase(1);

        $state = $this->updates->lastCheck();
        if ($state['status'] === 'available'
            && (array) $state['breaking_changes'] !== []
            && !$ignoreBreaking
        ) {
            throw new RuntimeException('The update path contains breaking changes. Confirm them on the Updates page to apply manually.');
        }

        $failures = [];
        $warnings = [];
        foreach ($this->updates->preFlight($targetVersion, false) as $check) {
            if (!$check['ok']) {
                $message = $check['name'] . ($check['detail'] !== '' ? ': ' . $check['detail'] : '');

                if ($check['hard']) {
                    $failures[] = $message;
                    $reporter->detail('FAILED (required): ' . $message);
                } else {
                    $warnings[] = $message;
                    $reporter->detail('Warning (optional): ' . $message);
                }

                continue;
            }

            $reporter->detail($check['name'] . ' - ok');
        }

        if ($failures !== []) {
            throw new RuntimeException('Preflight failed: ' . implode('; ', $failures));
        }

        return $warnings;
    }

    private function runBackup(UpdateProgress $reporter, string $triggeredBy): string
    {
        $reporter->beginPhase(2);

        try {
            $backups = $this->app->backups();
        } catch (Throwable $e) {
            throw new RuntimeException('The Backups plugin is not available: ' . $e->getMessage());
        }

        $backupDir = rtrim($backups->getBackupDir(), '/');
        $lock      = new BackupProgressReporter('backup', $backupDir);

        if (!$lock->acquireLock()) {
            throw new RuntimeException('A backup or restore operation is already running.');
        }

        try {
            return $backups->createBackup(
                'pre-update',
                $triggeredBy,
                function (int $step, int $total, string $label) use ($reporter): void {
                    $reporter->detail(sprintf('[%d/%d] %s', $step, $total, $label));
                }
            );
        } finally {
            $lock->releaseLock();
        }
    }

    private function runDownload(UpdateProgress $reporter, string $targetVersion): string
    {
        $reporter->beginPhase(3);

        $release = null;
        foreach ($this->updates->fetchReleases() as $entry) {
            if ((string) $entry['version'] === $targetVersion) {
                $release = $entry;
                break;
            }
        }

        if ($release === null) {
            throw new RuntimeException("Release {$targetVersion} is not in the release feed.");
        }

        $url = $release['download_url'] ?? null;
        if (!is_string($url) || $url === '') {
            throw new RuntimeException("Release {$targetVersion} has no download URL in the feed.");
        }

        $zipPath = $this->storageDir() . '/pubvana-' . $targetVersion . '.zip';

        if (!$this->download($url, $zipPath, $reporter)) {
            throw new RuntimeException('The update download failed.');
        }

        return $zipPath;
    }

    private function runExtract(UpdateProgress $reporter, string $zipPath): string
    {
        $reporter->beginPhase(4);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('The downloaded file is not a readable zip archive.');
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name)) {
                $names[] = $name;
            }
        }
        $zip->close();

        if (!self::zipEntriesAreSafe($names)) {
            throw new RuntimeException('The zip contains unsafe paths and was rejected.');
        }

        $innerDir = self::detectInnerDir($names);
        $reporter->detail(count($names) . ' entries' . ($innerDir !== null ? ", wrapped in {$innerDir}/" : ''));

        $reporter->beginPhase(5);

        $extractPath = $this->storageDir() . '/extract';
        self::removeDirectory($extractPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true || !$zip->extractTo($extractPath)) {
            throw new RuntimeException('Could not extract the release zip.');
        }
        $zip->close();

        $source = $innerDir !== null ? $extractPath . '/' . $innerDir : $extractPath;

        if (!is_dir($source . '/app') && !is_file($source . '/pubvana.json')) {
            throw new RuntimeException('The release layout is unexpected: no app/ directory or pubvana.json found.');
        }

        return $source;
    }

    private function runCopy(UpdateProgress $reporter, string $source): void
    {
        $reporter->beginPhase(6);

        $protected = (array) ($this->config['protected_paths'] ?? self::defaultProtectedPaths());
        $items     = scandir($source);

        if ($items === false) {
            throw new RuntimeException('Could not read the extracted release.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (self::isProtected($item, array_values($protected))) {
                $reporter->detail('Skipping protected: ' . $item);
                continue;
            }

            $from = $source . '/' . $item;
            $to   = $this->projectRoot() . '/' . $item;

            if (is_dir($from)) {
                $count = self::copyDirectory($from, $to, function (int $files) use ($reporter, $item): void {
                    $reporter->detail('Copying ' . $item . '/ (' . number_format($files) . ' files)');
                });
                $reporter->detail('Copied ' . $item . '/ (' . number_format($count) . ' files)');
            } else {
                if (!copy($from, $to)) {
                    throw new RuntimeException('Could not copy file: ' . $item);
                }
                $reporter->detail('Copied ' . $item);
            }
        }
    }

    /**
     * Run pending migrations for the freshly copied code.
     *
     * Primary: a fresh `runway migrate` process (correct boot, sees new
     * plugins). Fallback on no-exec hosts: the migrations package runner
     * in-process against the live module set. A migration failure does
     * not abort the update (files are already in place); it is reported
     * in the result so the admin can run migrations manually.
     */
    private function runMigrations(UpdateProgress $reporter): ?string
    {
        $reporter->beginPhase(7);

        if ($this->execAvailable()) {
            $cmd = sprintf(
                'cd %s && php %s migrate 2>&1',
                escapeshellarg($this->projectRoot()),
                escapeshellarg($this->projectRoot() . '/runway')
            );
            exec($cmd, $output, $code);

            if ($code === 0) {
                $reporter->detail('Migrations applied via runway.');
                return null;
            }

            return 'runway migrate exited with code ' . $code . '. Run "php runway migrate" manually.';
        }

        try {
            $setup = new \Enlivenapp\Migrations\Services\MigrationSetup(
                $this->app->db(),
                (array) ($this->app->get('migrations') ?? []),
                $this->projectRoot()
            );
            $setup->runMigrate();
            $reporter->detail('Migrations applied in-process.');

            return null;
        } catch (Throwable $e) {
            return 'Migrations need a manual run ("php runway migrate"): ' . $e->getMessage();
        }
    }

    private function runCleanup(UpdateProgress $reporter, string $zipPath): string
    {
        $reporter->beginPhase(8);

        @unlink($zipPath);
        self::removeDirectory($this->storageDir() . '/extract');

        // Clear the runtime cache: the old code's compiled artifacts are stale.
        $cacheDir = $this->projectRoot() . '/writable/cache';
        if (is_dir($cacheDir)) {
            $entries = scandir($cacheDir) ?: [];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === '.gitkeep') {
                    continue;
                }
                $path = $cacheDir . '/' . $entry;
                is_dir($path) ? self::removeDirectory($path) : @unlink($path);
            }
        }

        return UpdateService::readManifestVersion($this->projectRoot() . '/pubvana.json') ?? 'unknown';
    }

    // ------------------------------------------------------------------
    // Download
    // ------------------------------------------------------------------

    /**
     * Stream the release zip with byte-level progress reporting.
     */
    private function download(string $url, string $destination, UpdateProgress $reporter): bool
    {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                $lastReported = 0;
                $timeout      = (int) ($this->config['download_timeout'] ?? 300);
                $userAgent    = (string) ($this->config['user_agent'] ?? '');
                if ($userAgent === '') {
                    $userAgent = 'Pubvana-Updates/3.0';
                }

                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $userAgent,
                    CURLOPT_NOPROGRESS     => false,
                    CURLOPT_XFERINFOFUNCTION => function ($resource, int $dlTotal, int $dlNow, int $ulTotal, int $ulNow) use ($reporter, &$lastReported): int {
                        if ($dlNow - $lastReported >= 262144) {
                            $lastReported = $dlNow;
                            $reporter->detail(self::downloadDetail($dlTotal, $dlNow));
                        }
                        return 0;
                    },
                ]);

                $body   = curl_exec($handle);
                $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                curl_close($handle);

                if (is_string($body) && $body !== '' && ($status === 200 || $status === 0)) {
                    // Status 0 covers non-HTTP schemes (file://) where
                    // curl reports no response code at all.
                    return file_put_contents($destination, $body) !== false;
                }

                return false;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout'         => (int) ($this->config['download_timeout'] ?? 300),
                'follow_location' => 1,
                'max_redirects'   => 3,
                'user_agent'      => (string) ($this->config['user_agent'] ?? 'Pubvana-Updates/3.0'),
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        return is_string($body) && $body !== '' && file_put_contents($destination, $body) !== false;
    }

    /**
     * Human download progress line.
     */
    public static function downloadDetail(int $totalBytes, int $currentBytes): string
    {
        if ($totalBytes > 0) {
            $percent = (int) floor($currentBytes / $totalBytes * 100);

            return sprintf(
                '%s of %s (%d%%)',
                self::humanSize($currentBytes),
                self::humanSize($totalBytes),
                min(100, $percent)
            );
        }

        return self::humanSize($currentBytes) . ' downloaded';
    }

    public static function humanSize(int $bytes): string
    {
        $size = (float) $bytes;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024) {
                return number_format($size, 1) . ' ' . $unit;
            }
            $size /= 1024;
        }

        return number_format($size, 1) . ' TB';
    }

    // ------------------------------------------------------------------
    // Filesystem helpers
    // ------------------------------------------------------------------

    /**
     * True when every zip entry path is safe to extract.
     *
     * Rejects traversal (".."), absolute paths, drive letters, and
     * backslash separators are normalized first so "\..\evil" cannot
     * sneak through.
     *
     * @param list<string> $names
     */
    public static function zipEntriesAreSafe(array $names): bool
    {
        foreach ($names as $name) {
            if ($name === '' || str_contains($name, "\0")) {
                return false;
            }

            $normalized = str_replace('\\', '/', $name);

            if (str_starts_with($normalized, '/') || preg_match('#^[a-zA-Z]:#', $normalized) === 1) {
                return false;
            }

            $segments = explode('/', $normalized);
            if (in_array('..', $segments, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect a release zip whose contents sit inside one wrapper directory
     * (GitHub zipball style: "pubvana-3.0.1/app/...").
     *
     * @param list<string> $names
     */
    public static function detectInnerDir(array $names): ?string
    {
        $tops = [];

        foreach ($names as $name) {
            $normalized = rtrim(str_replace('\\', '/', $name), '/');
            if ($normalized === '') {
                continue;
            }

            $segment = strstr($normalized, '/', true);
            $tops[$segment === false ? $normalized : $segment] = true;
        }

        if (count($tops) !== 1) {
            return null;
        }

        $top = (string) array_key_first($tops);

        // A wrapper dir must actually contain things below it.
        foreach ($names as $name) {
            $normalized = rtrim(str_replace('\\', '/', $name), '/');
            if (str_starts_with($normalized, $top . '/')) {
                return $top;
            }
        }

        return null;
    }

    /**
     * Is a release-relative path protected from being overwritten?
     *
     * @param array<int|string, mixed> $protected
     */
    public static function isProtected(string $relative, array $protected): bool
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        foreach ($protected as $entry) {
            if (!is_string($entry) || $entry === '') {
                continue;
            }

            $entry = trim(str_replace('\\', '/', $entry), '/');

            if ($relative === $entry || str_starts_with($relative, $entry . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copy a directory tree recursively. Returns the file count.
     *
     * Directories are created 0755; new directories inherit no odd modes.
     *
     * @param callable|null $onProgress fn(int $fileCount)
     */
    public static function copyDirectory(string $source, string $destination, ?callable $onProgress = null): int
    {
        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            throw new RuntimeException('Could not create directory: ' . $destination);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $files = 0;

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                continue;
            }

            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException('Could not copy file: ' . $target);
            }

            $files++;
            if ($onProgress !== null && $files % 25 === 0) {
                $onProgress($files);
            }
        }

        return $files;
    }

    /**
     * Remove a directory and everything inside it.
     */
    public static function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path . '/' . $item;
            is_dir($full) ? self::removeDirectory($full) : @unlink($full);
        }

        @rmdir($path);
    }

    /**
     * @return list<string>
     */
    public static function defaultProtectedPaths(): array
    {
        return ['.env', 'app/config/shield.php', 'writable'];
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function storageDir(): string
    {
        return rtrim((string) ($this->config['updates_path'] ?? ''), '/');
    }

    private function projectRoot(): string
    {
        return defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3);
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions') ?: '';

        return !in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }
}
