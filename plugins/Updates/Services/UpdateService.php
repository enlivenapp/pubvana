<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\Services;

use flight\Engine;
use Throwable;

/**
 * Release discovery, safe-target selection, and preflight checks.
 *
 * Talks to the releases.json feed, keeps the 24h-cached check state in the
 * settings store, and computes which release a site may safely move to.
 * Pure version/target logic lives in static methods so it is testable
 * without network or filesystem access.
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class UpdateService
{
    private const SETTING_LAST_CHECK_AT     = 'Updates.lastCheckAt';
    private const SETTING_LAST_CHECK_RESULT = 'Updates.lastCheckResult';
    private const SETTING_AUTO_UPDATE       = 'Updates.autoUpdate';
    private const SETTING_SKIPPED           = 'Updates.skippedVersions';

    /** @var Engine<object> */
    private Engine $app;

    /** @var array<string, mixed> */
    private array $config;

    private ?string $currentVersion = null;

    /**
     * @param Engine<object>          $app
     * @param array<string, mixed>    $config
     */
    public function __construct(Engine $app, array $config = [])
    {
        $this->app    = $app;
        $this->config = $config;
    }

    // ------------------------------------------------------------------
    // Configuration accessors
    // ------------------------------------------------------------------

    public function releasesUrl(): string
    {
        return (string) ($this->config['releases_url'] ?? '');
    }

    public function storageDir(): string
    {
        return rtrim((string) ($this->config['updates_path'] ?? ''), '/');
    }

    public function manifestPath(): string
    {
        return (string) ($this->config['manifest_path'] ?? '');
    }

    public function checkCacheHours(): int
    {
        return max(1, (int) ($this->config['check_cache_hours'] ?? 24));
    }

    // ------------------------------------------------------------------
    // Settings
    // ------------------------------------------------------------------

    public function autoUpdateEnabled(): bool
    {
        return (bool) $this->app->settings()->get(self::SETTING_AUTO_UPDATE, false);
    }

    public function setAutoUpdate(bool $enabled): void
    {
        $this->app->settings()->set(self::SETTING_AUTO_UPDATE, $enabled);
    }

    /**
     * @return list<string>
     */
    public function skippedVersions(): array
    {
        $value = $this->app->settings()->get(self::SETTING_SKIPPED, []);

        return is_array($value) ? array_values(array_map('strval', $value)) : [];
    }

    public function skipVersion(string $version): void
    {
        $skipped = $this->skippedVersions();
        if (!in_array($version, $skipped, true)) {
            $skipped[] = $version;
            $this->app->settings()->set(self::SETTING_SKIPPED, $skipped);
        }
    }

    public function unskipVersion(string $version): void
    {
        $skipped = array_values(array_filter(
            $this->skippedVersions(),
            static fn(string $v): bool => $v !== $version
        ));
        $this->app->settings()->set(self::SETTING_SKIPPED, $skipped);
    }

    // ------------------------------------------------------------------
    // Version sources
    // ------------------------------------------------------------------

    /**
     * The installed Pubvana version from the root pubvana.json manifest.
     */
    public function currentVersion(): string
    {
        if ($this->currentVersion === null) {
            $this->currentVersion = self::readManifestVersion($this->manifestPath()) ?? '0.0.0';
        }

        return $this->currentVersion;
    }

    /**
     * Read the semver from a pubvana.json manifest.
     */
    public static function readManifestVersion(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $raw  = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($data)) {
            return null;
        }

        $version = $data['semver'] ?? $data['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : null;
    }

    // ------------------------------------------------------------------
    // Release feed
    // ------------------------------------------------------------------

    /**
     * Fetch and sort the release feed. Newest first.
     *
     * @return list<array<string, mixed>>
     * @throws \RuntimeException When the feed is unreachable or malformed
     */
    public function fetchReleases(): array
    {
        $body = $this->httpGet($this->releasesUrl(), (int) ($this->config['check_timeout'] ?? 5));

        if ($body === null) {
            throw new \RuntimeException('Could not fetch the release feed (' . $this->releasesUrl() . ').');
        }

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['releases']) || !is_array($data['releases'])) {
            throw new \RuntimeException('The release feed returned malformed data.');
        }

        $releases = array_values(array_filter(
            $data['releases'],
            static fn(mixed $release): bool => is_array($release) && isset($release['version']) && is_string($release['version'])
        ));

        usort($releases, static fn(array $a, array $b): int => version_compare((string) $b['version'], (string) $a['version']));

        return $releases;
    }

    /**
     * HTTP GET with curl and a file_get_contents fallback.
     */
    protected function httpGet(string $url, int $timeoutSeconds): ?string
    {
        $userAgent = $this->userAgent();

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS      => 3,
                    CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                    CURLOPT_TIMEOUT        => $timeoutSeconds,
                    CURLOPT_USERAGENT      => $userAgent,
                ]);

                $body   = curl_exec($handle);
                $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                curl_close($handle);

                if (is_string($body) && $body !== '' && ($status === 200 || $status === 0)) {
                    // Status 0 covers non-HTTP schemes (file://) where
                    // curl reports no response code at all.
                    return $body;
                }

                return null;
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout'         => $timeoutSeconds,
                'follow_location' => 1,
                'max_redirects'   => 3,
                'user_agent'      => $userAgent,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        return is_string($body) && $body !== '' ? $body : null;
    }

    /**
     * User agent for feed requests, narrowed to non-empty.
     *
     * @return non-empty-string
     */
    private function userAgent(): string
    {
        $agent = (string) ($this->config['user_agent'] ?? '');

        return $agent !== '' ? $agent : 'Pubvana-Updates/3.0';
    }

    // ------------------------------------------------------------------
    // Check state
    // ------------------------------------------------------------------

    /**
     * Is the cached check result stale enough to re-fetch?
     */
    public function isDue(): bool
    {
        $last = $this->app->settings()->get(self::SETTING_LAST_CHECK_AT);

        if (!is_string($last) || $last === '') {
            return true;
        }

        $checked = strtotime($last);

        return $checked === false || (time() - $checked) > $this->checkCacheHours() * 3600;
    }

    /**
     * Run (or reuse) a release check and persist the result.
     *
     * @return array<string, mixed> The check state, see checkState()
     */
    public function check(bool $force = false): array
    {
        if (!$force && !$this->isDue()) {
            return $this->lastCheck();
        }

        $current = $this->currentVersion();

        try {
            $releases = $this->fetchReleases();
        } catch (\RuntimeException $e) {
            return $this->persistCheck($this->errorState($current, $e->getMessage()));
        }

        $latest = $releases[0] ?? null;

        if ($latest === null || version_compare($current, (string) $latest['version']) >= 0) {
            return $this->persistCheck($this->state($current, 'up_to_date', null, $latest));
        }

        $constraints = self::scanManifests($this->scanRoot(), ['plugins', 'themes']);
        $picked      = self::pickTarget($current, $releases, $constraints, $this->skippedVersions());

        $target = $picked['target'];
        $state  = $target === null
            ? $this->state($current, 'up_to_date', null, $latest, $picked['capped_by'])
            : $this->state($current, 'available', $target, $latest, $picked['capped_by']);

        if ($picked['capped_by'] !== null) {
            $state['constraints'] = self::rejectingConstraints((string) $latest['version'], $constraints);
        }

        if ($target !== null) {
            $state['breaking_changes'] = self::collectField($releases, $current, (string) $target['version'], 'breaking_changes');
            $state['migration_notes']  = self::collectField($releases, $current, (string) $target['version'], 'migration_notes');
            $state['notices']          = self::collectField($releases, $current, (string) $target['version'], 'notices');
        }

        return $this->persistCheck($state);
    }

    /**
     * The cached check state without touching the network.
     *
     * @return array<string, mixed>
     */
    public function lastCheck(): array
    {
        $stored = $this->app->settings()->get(self::SETTING_LAST_CHECK_RESULT);

        if (is_array($stored) && isset($stored['status'])) {
            $stored['stale'] = $this->isDue();
            return $stored;
        }

        return $this->errorState($this->currentVersion(), 'No update check has run yet.');
    }

    /**
     * Persist a check result to the settings store.
     *
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function persistCheck(array $state): array
    {
        $this->app->settings()->set(self::SETTING_LAST_CHECK_AT, date('c'));
        $this->app->settings()->set(self::SETTING_LAST_CHECK_RESULT, $state);

        return $state;
    }

    // ------------------------------------------------------------------
    // Automatic update chain
    // ------------------------------------------------------------------

    /**
     * The automatic update chain: force-check, then apply when allowed.
     *
     * Single implementation shared by both consumers: the
     * `updates:auto-update` command and the core cron task (24h slot).
     * Applies only when the autoUpdate setting is on, a target exists,
     * and no breaking changes are in range; the pre-update backup inside
     * UpdateApplyService is the final gate.
     *
     * @param  callable|null $onProgress  fn(string $label, string $detail): void
     * @param  string        $triggeredBy Attribution recorded in backup metadata
     * @return array{status: string, message: string, version: ?string}
     */
    public function runAutoUpdateChain(?callable $onProgress = null, string $triggeredBy = 'cron'): array
    {
        try {
            $state = $this->check(true);
        } catch (\RuntimeException $e) {
            return ['status' => 'error', 'message' => 'Check failed: ' . $e->getMessage(), 'version' => null];
        }

        $status = (string) ($state['status'] ?? 'error');
        $target = (string) ($state['target_version'] ?? '');

        if ($status === 'up_to_date') {
            return ['status' => 'noop', 'message' => 'Up to date. Nothing to do.', 'version' => null];
        }

        if ($status === 'error') {
            return ['status' => 'error', 'message' => (string) ($state['error'] ?? 'unknown error'), 'version' => null];
        }

        if (!$this->autoUpdateEnabled()) {
            return [
                'status'  => 'noop',
                'message' => 'Version ' . ($target !== '' ? $target : '?') . ' is available. Automatic updates are off; nothing applied.',
                'version' => null,
            ];
        }

        if (((array) ($state['breaking_changes'] ?? [])) !== []) {
            return [
                'status'  => 'refused',
                'message' => 'Breaking changes in the update path. Automatic updates refuse to apply; apply manually from Tools > Updates.',
                'version' => $target !== '' ? $target : null,
            ];
        }

        if ($target === '') {
            return ['status' => 'noop', 'message' => 'No applicable update target.', 'version' => null];
        }

        $apply   = new UpdateApplyService($this->app, $this->config);
        $applied = $apply->apply($target, $triggeredBy, false, $onProgress);

        if ($applied) {
            return ['status' => 'ok', 'message' => 'Automatic update applied.', 'version' => $target];
        }

        $progress = (new UpdateProgress($this->storageDir()))->read();

        return [
            'status'  => 'error',
            'message' => 'Automatic update failed: ' . (string) ($progress['error'] ?? 'unknown error'),
            'version' => $target,
        ];
    }

    /**
     * Build a normalized check state.
     *
     * @param array<string, mixed>|null $target
     * @param array<string, mixed>|null $latest
     * @return array<string, mixed>
     */
    private function state(string $current, string $status, ?array $target, ?array $latest, ?string $cappedBy = null): array
    {
        return [
            'status'           => $status,
            'current_version'  => $current,
            'latest_version'   => $latest['version'] ?? null,
            'target_version'   => $target['version'] ?? null,
            'target'           => $target,
            'breaking_changes' => [],
            'migration_notes'  => [],
            'notices'          => [],
            'capped_by'        => $cappedBy,
            'constraints'      => [],
            'checked_at'       => date('c'),
            'error'            => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorState(string $current, string $message): array
    {
        return [
            'status'           => 'error',
            'current_version'  => $current,
            'latest_version'   => null,
            'target_version'   => null,
            'target'           => null,
            'breaking_changes' => [],
            'migration_notes'  => [],
            'notices'          => [],
            'capped_by'        => null,
            'constraints'      => [],
            'checked_at'       => date('c'),
            'error'            => $message,
        ];
    }

    private function projectRoot(): string
    {
        return defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3);
    }

    /**
     * Where plugin/theme manifests are scanned for constraints.
     * Overridable via config for tests.
     */
    private function scanRoot(): string
    {
        $root = $this->config['scan_root'] ?? null;

        return is_string($root) && $root !== '' ? $root : $this->projectRoot();
    }

    // ------------------------------------------------------------------
    // Preflight
    // ------------------------------------------------------------------

    /**
     * Pre-apply checks for moving to a target version.
     *
     * @param  bool $includeLocks When false, the lock check is skipped:
     *         the apply flow calls this while already holding the update
     *         lock (self-conflict), and verifies the backups lock itself
     *         when it snapshots.
     * @return list<array{name: string, ok: bool, detail: string, hard: bool}>
     *         A failing "hard" (Required) check blocks the apply; a failing
     *         optional check is a warning only.
     */
    public function preFlight(string $targetVersion, bool $includeLocks = true): array
    {
        $checks   = [];
        $releases = null;

        try {
            $releases = $this->fetchReleases();
        } catch (\RuntimeException) {
            // Handled by the min-PHP check below falling back to null.
        }

        $checks[] = [
            'name'   => 'PHP version',
            'ok'     => self::phpVersionSatisfies($releases, $this->currentVersion(), $targetVersion),
            'detail' => 'Running PHP ' . PHP_VERSION,
            'hard'   => true,
        ];

        $checks[] = [
            'name'   => 'Free disk space',
            'ok'     => $this->diskOk(),
            'detail' => $this->diskDetail(),
            'hard'   => true,
        ];

        foreach ($this->writableTargets() as $path) {
            $checks[] = [
                'name'   => 'Writable: ' . $path['label'],
                'ok'     => $path['ok'],
                'detail' => $path['ok'] ? '' : 'Directory is missing or not writable.',
                'hard'   => true,
            ];
        }

        $checks[] = $this->backupsCheck();
        $checks[] = $this->execCheck();

        if ($includeLocks) {
            $checks[] = $this->locksCheck();
        }

        return $checks;
    }

    /**
     * Command-line availability. Optional: with exec disabled the update
     * runs in-process, which is slower and ties up the request.
     *
     * @return array{name: string, ok: bool, detail: string, hard: bool}
     */
    private function execCheck(): array
    {
        $available = $this->execAvailable();

        return [
            'name'   => 'Command line execution',
            'ok'     => $available,
            'detail' => $available
                ? 'Updates can run in the background.'
                : 'Updates run in-process; keep the page open until finished.',
            'hard'   => false,
        ];
    }

    private function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = ini_get('disable_functions') ?: '';

        return !in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }

    /**
     * Does the running PHP satisfy the min_php_version of releases in range?
     *
     * @param list<array<string, mixed>>|null $releases
     */
    public static function phpVersionSatisfies(?array $releases, string $current, string $target): bool
    {
        if ($releases === null) {
            return false;
        }

        $min = self::minPhpBetween($releases, $current, $target);

        return $min === null || version_compare(PHP_VERSION, $min) >= 0;
    }

    /**
     * The strictest min_php_version among releases between two versions.
     *
     * @param list<array<string, mixed>> $releases
     */
    public static function minPhpBetween(array $releases, string $from, string $to): ?string
    {
        $min = null;

        foreach (self::releasesBetween($releases, $from, $to) as $release) {
            $required = $release['min_php_version'] ?? null;
            if (!is_string($required) || $required === '') {
                continue;
            }

            if ($min === null || version_compare($required, $min) > 0) {
                $min = $required;
            }
        }

        return $min;
    }

    /**
     * Releases strictly newer than $from, up to and including $to.
     *
     * @param list<array<string, mixed>> $releases
     * @return list<array<string, mixed>>
     */
    public static function releasesBetween(array $releases, string $from, string $to): array
    {
        return array_values(array_filter(
            $releases,
            static fn(array $release): bool => version_compare((string) $release['version'], $from) > 0
                && version_compare((string) $release['version'], $to) <= 0
        ));
    }

    /**
     * Collect a string-list field (breaking_changes, notices, ...) across
     * every release between two versions.
     *
     * @param list<array<string, mixed>> $releases
     * @return list<string>
     */
    public static function collectField(array $releases, string $from, string $to, string $field): array
    {
        $collected = [];

        foreach (self::releasesBetween($releases, $from, $to) as $release) {
            $values = $release[$field] ?? null;
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (is_string($value) && $value !== '') {
                    $collected[] = $value;
                }
            }
        }

        return $collected;
    }

    /**
     * @return list<array{label: string, ok: bool}>
     */
    private function writableTargets(): array
    {
        $root = $this->projectRoot();

        $targets = [];
        foreach (['app', 'public', 'vendor', 'plugins', 'themes'] as $dir) {
            $path = $root . '/' . $dir;
            $targets[] = [
                'label' => $dir . '/',
                'ok'    => is_dir($path) && is_writable($path),
            ];
        }

        $targets[] = [
            'label' => 'project root',
            'ok'    => is_writable($root),
        ];

        return $targets;
    }

    private function diskOk(): bool
    {
        $bytes = disk_free_space($this->projectRoot());

        return $bytes !== false
            ? ($bytes / 1024 / 1024) >= (float) ($this->config['min_free_disk_mb'] ?? 500)
            : false;
    }

    private function diskDetail(): string
    {
        $bytes = disk_free_space($this->projectRoot());

        if ($bytes === false) {
            return 'Unknown';
        }

        return sprintf('%.1f GB free', $bytes / 1024 / 1024 / 1024);
    }

    /**
     * The Backups plugin must be available: it provides the mandatory
     * pre-update snapshot and the rollback path.
     *
     * @return array{name: string, ok: bool, detail: string, hard: bool}
     */
    private function backupsCheck(): array
    {
        try {
            $this->app->backups();
            $available = true;
        } catch (Throwable) {
            $available = false;
        }

        return [
            'name'   => 'Backups plugin',
            'ok'     => $available,
            'detail' => $available
                ? ''
                : 'Required for the mandatory pre-update backup. Enable the Backups plugin.',
            'hard'   => true,
        ];
    }

    /**
     * No update or backup operation may be holding a lock.
     *
     * @return array{name: string, ok: bool, detail: string, hard: bool}
     */
    private function locksCheck(): array
    {
        $storage = $this->storageDir();
        $updateLocked = $storage !== '' && UpdateProgress::isLockedInDir($storage);

        $backupDir  = rtrim((string) (($this->app->get('pubvana.backups')['backup_path'] ?? $this->projectRoot() . '/writable/backups')), '/');
        $backupLocked = is_file($backupDir . '/operation.lock');

        $ok = !$updateLocked && !$backupLocked;

        return [
            'name'   => 'No operation in progress',
            'ok'     => $ok,
            'detail' => $updateLocked
                ? 'An update is already running.'
                : ($backupLocked ? 'A backup or restore is running.' : ''),
            'hard'   => true,
        ];
    }

    // ------------------------------------------------------------------
    // Target selection (pure logic)
    // ------------------------------------------------------------------

    /**
     * Scan plugin and theme manifests for Pubvana version constraints.
     *
     * Only manifests that declare min_pubvana_version or
     * max_pubvana_version produce entries; absence means no constraint.
     *
     * @param list<string> $dirs Directory names under the project root to scan
     * @return array<string, array{min: ?string, max: ?string}> Keyed by manifest name
     */
    public static function scanManifests(string $projectRoot, array $dirs = ['plugins', 'themes']): array
    {
        $constraints = [];

        foreach ($dirs as $dir) {
            $manifests = glob($projectRoot . '/' . $dir . '/*/pubvana.json') ?: [];

            foreach ($manifests as $manifestPath) {
                $raw  = file_get_contents($manifestPath);
                $data = is_string($raw) ? json_decode($raw, true) : null;

                if (!is_array($data)) {
                    continue;
                }

                $min = $data['min_pubvana_version'] ?? null;
                $max = $data['max_pubvana_version'] ?? null;

                if (!is_string($min) && !is_string($max)) {
                    continue;
                }

                $name = is_string($data['name'] ?? null) ? (string) $data['name'] : basename(dirname($manifestPath));

                $constraints[$name] = [
                    'min' => is_string($min) && $min !== '' ? $min : null,
                    'max' => is_string($max) && $max !== '' ? $max : null,
                ];
            }
        }

        return $constraints;
    }

    /**
     * Pick the highest releasable target above $current.
     *
     * A candidate is allowed when no constraint rejects it. Iterating
     * newest-first means the first allowed candidate is the target; the
     * first rejection above it names the capping extension (v2's
     * safe-target behavior).
     *
     * @param list<array<string, mixed>> $releases
     * @param array<string, array{min: ?string, max: ?string}> $constraints
     * @param list<string> $skippedVersions
     * @return array{target: array<string, mixed>|null, capped_by: string|null}
     */
    public static function pickTarget(string $current, array $releases, array $constraints, array $skippedVersions): array
    {
        $sorted = $releases;
        usort($sorted, static fn(array $a, array $b): int => version_compare((string) $b['version'], (string) $a['version']));

        $candidates = array_values(array_filter(
            $sorted,
            static fn(array $release): bool => version_compare((string) $release['version'], $current) > 0
                && !in_array((string) $release['version'], $skippedVersions, true)
        ));

        if ($candidates === []) {
            return ['target' => null, 'capped_by' => null];
        }

        $cappedBy = null;

        foreach ($candidates as $candidate) {
            $version = (string) $candidate['version'];
            $blockedBy = self::firstRejectingConstraint($version, $constraints);

            if ($blockedBy === null) {
                return ['target' => $candidate, 'capped_by' => $cappedBy];
            }

            $cappedBy ??= $blockedBy;
        }

        return ['target' => null, 'capped_by' => $cappedBy];
    }

    /**
     * The first constraint that rejects a version, or null when allowed.
     *
     * @param array<string, array{min: ?string, max: ?string}> $constraints
     */
    private static function firstRejectingConstraint(string $version, array $constraints): ?string
    {
        foreach ($constraints as $name => $constraint) {
            if ($constraint['min'] !== null && version_compare($version, $constraint['min']) < 0) {
                return $name;
            }

            if ($constraint['max'] !== null && version_compare($version, $constraint['max']) > 0) {
                return $name;
            }
        }

        return null;
    }

    /**
     * The constraints that reject a version, for display on the Updates page.
     *
     * @param array<string, array{min: ?string, max: ?string}> $constraints
     * @return list<array{name: string, min: ?string, max: ?string}>
     */
    public static function rejectingConstraints(string $version, array $constraints): array
    {
        $list = [];

        foreach ($constraints as $name => $constraint) {
            $rejects = ($constraint['min'] !== null && version_compare($version, $constraint['min']) < 0)
                || ($constraint['max'] !== null && version_compare($version, $constraint['max']) > 0);

            if ($rejects) {
                $list[] = ['name' => $name, 'min' => $constraint['min'], 'max' => $constraint['max']];
            }
        }

        return $list;
    }

    // ------------------------------------------------------------------
    // Addon inventory (Updates page listing)
    // ------------------------------------------------------------------

    /**
     * Inventory of installed themes, blocks, and plugins for the Updates
     * page. Read-only, no network: update sources do not exist yet (the
     * Marketplace will register them), so themes/plugins carry name, id,
     * and version ("No update source" on the page) and blocks carry who
     * updates them. Themes carry the folder and plugins the package id so
     * the admin screen can stamp each row with its trust standing.
     *
     * @return array{themes: list<array{name: string, folder: ?string, version: ?string}>, blocks: list<array{name: string, updates_with: string}>, plugins: list<array{name: string, id: string, version: ?string}>}
     */
    public function addons(): array
    {
        return [
            'themes'  => $this->inventoryThemes(),
            'blocks'  => $this->inventoryBlocks(),
            'plugins' => $this->inventoryPlugins(),
        ];
    }

    /**
     * @return list<array{name: string, folder: ?string, version: ?string}>
     */
    private function inventoryThemes(): array
    {
        try {
            $themes = $this->app->themes()->discover();
        } catch (Throwable) {
            return [];
        }

        $rows = [];
        foreach ($themes as $theme) {
            $name = $theme['display_name'] ?? $theme['name'] ?? $theme['folder'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $version = $theme['semver'] ?? $theme['version'] ?? null;

            $rows[] = [
                'name'    => $name,
                'folder'  => is_string($theme['folder'] ?? null) ? $theme['folder'] : null,
                'version' => is_string($version) && $version !== '' ? $version : null,
            ];
        }

        return $rows;
    }

    /**
     * Blocks are not versioned installs: they are version-locked to the
     * plugin or core that registers them, so each row carries who updates
     * it instead of a version. Owners come from the known plugin ids
     * (contributor keys are prefixed with them); pubvana-namespaced keys
     * with no matching plugin fall back to the key's second segment.
     *
     * @return list<array{name: string, updates_with: string}>
     */
    private function inventoryBlocks(): array
    {
        try {
            $blocks = $this->app->adext()->get('block', 'available');
        } catch (Throwable) {
            return [];
        }

        $pluginNames = $this->pluginDisplayNames();

        $rows = [];
        foreach ($blocks as $key => $block) {
            $label = $block['label'] ?? null;

            if (!is_string($label) || $label === '') {
                $label = $key;
            }

            if ($label === '') {
                continue;
            }

            $updatesWith = null;
            foreach ($pluginNames as $pluginId => $pluginName) {
                if ($pluginId !== '' && str_starts_with($key, $pluginId . '.')) {
                    $updatesWith = $pluginName;
                    break;
                }
            }

            if ($updatesWith === null) {
                $segments = explode('.', $key);
                if ($segments[0] === 'pubvana' || str_starts_with($segments[0], 'pubvana/')) {
                    $updatesWith = isset($segments[1]) && $segments[1] !== ''
                        ? ucfirst(str_replace(['-', '_'], ' ', $segments[1]))
                        : 'Pubvana core';
                } else {
                    $updatesWith = $key;
                }
            }

            $rows[] = ['name' => $label, 'updates_with' => $updatesWith];
        }

        return $rows;
    }

    /**
     * Display names for every installed plugin id: local plugins use their
     * manifest display name (id as fallback), vendor packages use the
     * package id.
     *
     * @return array<string, string>
     */
    private function pluginDisplayNames(): array
    {
        $names = [];

        try {
            $local = $this->app->pluginLoader()->discoverLocal();
        } catch (Throwable) {
            $local = [];
        }

        foreach ($local as $pluginId => $info) {
            $name = is_array($info['manifest'] ?? null) ? ($info['manifest']['display_name'] ?? null) : null;
            if (!is_string($name) || $name === '') {
                $name = $pluginId;
            }

            if ($name !== '') {
                $names[$pluginId] = $name;
            }
        }

        try {
            $vendor = $this->app->pluginLoader()->discoverVendor();
        } catch (Throwable) {
            $vendor = [];
        }

        foreach ($vendor as $packageId => $info) {
            if (!isset($names[$packageId])) {
                $names[$packageId] = $packageId;
            }
        }

        return $names;
    }

    /**
     * @return list<array{name: string, id: string, version: ?string}>
     */
    private function inventoryPlugins(): array
    {
        /** @var array<string, array{name: string, id: string, version: ?string}> $rows */
        $rows = [];
        $names = $this->pluginDisplayNames();

        try {
            $local = $this->app->pluginLoader()->discoverLocal();
        } catch (Throwable) {
            $local = [];
        }

        foreach ($local as $pluginId => $info) {
            $version = $info['version'] ?? null;

            $rows[$pluginId] = [
                'name'    => $names[$pluginId] ?? $pluginId,
                'id'      => (string) $pluginId,
                'version' => is_string($version) && $version !== '' ? $version : null,
            ];
        }

        try {
            $vendor = $this->app->pluginLoader()->discoverVendor();
        } catch (Throwable) {
            $vendor = [];
        }

        foreach ($vendor as $packageId => $info) {
            if (isset($rows[$packageId])) {
                continue;
            }

            $version = $info['version'] ?? null;

            $rows[$packageId] = [
                'name'    => $names[$packageId] ?? $packageId,
                'id'      => (string) $packageId,
                'version' => is_string($version) && $version !== '' ? $version : null,
            ];
        }

        ksort($rows);

        return array_values($rows);
    }
}
