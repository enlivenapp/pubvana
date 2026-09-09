<?php

declare(strict_types=1);

namespace Pubvana\Services;

use Pubvana\Models\TrustCache;
use flight\Engine;

/**
 * TrustClientService - Client for the Pubvana trust service at pubvanacms.com.
 *
 * Every Pubvana v3 install uses this client; the trust service itself (the
 * Trust plugin) runs only on the home site. The client collects the addons
 * installed here, asks the check API for each one's standing (trusted, known,
 * malicious, unknown), caches the answers in the trust_cache table, and
 * surfaces them in the admin plugins/themes screens and the activation gate.
 *
 * Contact discipline (the home site is a free shared service, do not flood it):
 *
 *   - A full batch runs at most once per CACHE_TTL_HOURS, from the 4h cron.
 *     A failed batch leaves the timestamp untouched, so the next 4h tick
 *     retries; a successful one stamps TrustClient.last_check_at.
 *   - Addons cached 'trusted' are never rechecked. An addon version change
 *     is a new cache row, so updates recheck naturally.
 *   - Addons not in the cache (fresh discovery) are checked in one batch the
 *     first time the admin opens the plugins or themes page.
 *   - The activation gate and manual rechecks always hit the API live, one
 *     item at a time.
 *
 * Home-site push: every check response may carry a "malicious" list, entries
 * of {"type": "plugin"|"theme", "slug": "..."} naming addons the trust service
 * has found to be malicious. The list never carries verdicts, it is a prompt
 * to re-ask. The client stores it in writable/cache/trust-malicious.json as
 * {"current": [...], "processed": [...]}:
 *
 *   - Cron runs diff current against processed and re-ask (one small batch,
 *     trusted-skip overridden) for entries matching an installed addon, TTL
 *     independent, so a post-activation malicious finding surfaces within one
 *     4h tick. processed advances only after the re-ask succeeds. The diff
 *     means each finding triggers exactly one small request, ever.
 *   - Web requests (activation gate, manual recheck) only refresh the file's
 *     current list, they never chain follow-up requests while an admin waits.
 *   - Persistent list entries are exempt from the trusted-skip in the regular
 *     TTL batch, so the client keeps confirming them on the normal cadence.
 *
 * Response envelope contract (for the home-site build):
 *
 *   {"results": [{"type","slug","version","status","warning"}...],
 *    "malicious": [{"type","slug"}...]}
 *
 * @package Pubvana\Services
 */
class TrustClientService
{
    public const TRUST_API_URL = 'http://localhost/api/trust/v1/check';

    /** Hours a successful full batch keeps the client from asking again. */
    public const CACHE_TTL_HOURS = 24;

    /** HTTP timeout in seconds. Short: the activation gate waits on this. */
    public const HTTP_TIMEOUT = 5;

    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_THEME  = 'theme';

    public const ORIGIN_COMPOSER = 'composer';
    public const ORIGIN_LOCAL    = 'local';

    public const LAST_CHECK_KEY = 'TrustClient.last_check_at';

    /** Cache-file hygiene: drop unconfirmed rows older than this many hours. */
    private const PURGE_AFTER_HOURS = 720;

    private \PDO $pdo;

    /** @var Engine<object> */
    private Engine $app;

    private string $maliciousFile;

    private ?string $pubvanaVersion = null;

    /**
     * @param Engine<object> $app
     * @param string|null    $maliciousFile Override the cache-file path (tests)
     * @param string|null    $apiUrl        Override the API endpoint (tests)
     */
    public function __construct(\PDO $pdo, Engine $app, ?string $maliciousFile = null, private ?string $apiUrl = null)
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->maliciousFile = $maliciousFile
            ?? PROJECT_ROOT . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'trust-malicious.json';
    }

    // -----------------------------------------------------------------
    // Cron entry
    // -----------------------------------------------------------------

    /**
     * The 4h cron task. Honors new home-site malicious findings first, then
     * runs the TTL-gated batch over installed addons.
     */
    public function checkIfDue(): void
    {
        $state = $this->maliciousListRead();
        $currentList = $state['current'];
        $processed = $state['processed'];
        $latestList = $currentList;

        // 1. New malicious findings: re-ask immediately, TTL independent.
        $newEntries = $this->listDiff($currentList, $processed);
        if ($newEntries !== []) {
            $matches = $this->matchInstalled($newEntries);
            if ($matches === []) {
                // Nothing installed matches; the findings are fully handled.
                $processed = $currentList;
            } else {
                $outcome = $this->checkAddons($matches);
                if ($outcome['ok']) {
                    $processed = $currentList;
                    if ($outcome['malicious'] !== []) {
                        $latestList = $outcome['malicious'];
                    }
                }
                // On failure processed stays put, the next tick retries.
            }
        }

        // 2. The TTL-gated batch.
        if (!$this->isCacheFresh()) {
            $items = $this->collectInstalledAddons();
            $due = $this->filterDueItems($items, $currentList);
            if ($due !== []) {
                $outcome = $this->checkAddons($due);
                if ($outcome['ok']) {
                    $this->stampLastCheck();
                    if ($outcome['malicious'] !== []) {
                        $latestList = $outcome['malicious'];
                    }
                }
            } else {
                // Every addon is trusted-cached and nothing is on the list:
                // a quiet cycle still counts against the TTL.
                $this->stampLastCheck();
            }

            try {
                $this->cacheModel()->purgeStale(self::PURGE_AFTER_HOURS);
            } catch (\Throwable) {
                // Hygiene must never break the cron run.
            }
        }

        $this->maliciousListWrite($latestList, $processed);
    }

    // -----------------------------------------------------------------
    // Checks
    // -----------------------------------------------------------------

    /**
     * Check one addon live (activation gate, manual recheck). Always hits the
     * API; the result is cached on success. A network failure answers
     * 'unknown' with answered=false and writes nothing to the cache, so an
     * outage cannot mark anything trusted.
     *
     * @return array{status: string, warning: ?string, answered: bool}
     */
    public function checkAddon(string $type, string $slug, string $version, string $author, string $origin = self::ORIGIN_LOCAL): array
    {
        $data = $this->requestCheck([[
            'type'    => $type,
            'slug'    => $slug,
            'version' => $version,
            'author'  => $author,
            'origin'  => $origin,
        ]]);

        if ($data === null) {
            return ['status' => TrustCache::STATUS_UNKNOWN, 'warning' => null, 'answered' => false];
        }

        $status = TrustCache::STATUS_UNKNOWN;
        $warning = null;
        foreach ($data['results'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((string) ($row['type'] ?? '') === $type
                && (string) ($row['slug'] ?? '') === $slug
                && (string) ($row['version'] ?? '') === $version) {
                $candidate = (string) ($row['status'] ?? '');
                $status = $this->validStatus($candidate);
                $warning = isset($row['warning']) && is_string($row['warning']) && $row['warning'] !== ''
                    ? $row['warning']
                    : null;
                break;
            }
        }

        $this->cacheModel()->upsert($type, $slug, $version, $author, $status, $warning);

        // Web path: refresh the home-site list, never chain follow-ups here.
        $list = $this->extractMaliciousList($data);
        if ($list !== []) {
            $state = $this->maliciousListRead();
            $this->maliciousListWrite($list, $state['processed']);
        }

        return ['status' => $status, 'warning' => $warning, 'answered' => true];
    }

    /**
     * Check a batch of addons. Upserts every answer into the cache.
     *
     * @param list<array{type: string, slug: string, version: string, author: string, origin?: string}> $items
     * @return array{ok: bool, results: list<array{type: string, slug: string, version: string, status: string, warning: ?string}>, malicious: list<array{type: string, slug: string}>}
     */
    public function checkAddons(array $items): array
    {
        $empty = ['ok' => false, 'results' => [], 'malicious' => []];
        if ($items === []) {
            return ['ok' => true, 'results' => [], 'malicious' => []];
        }

        $data = $this->requestCheck($items);
        if ($data === null) {
            return $empty;
        }

        // The API echoes type/slug/version but not author; match answers back
        // to the items we sent to recover the full identity for the cache.
        $byIdentity = [];
        foreach ($items as $item) {
            $byIdentity[$item['type'] . '|' . $item['slug'] . '|' . $item['version']] = $item;
        }

        $results = [];
        foreach ($data['results'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = (string) ($row['type'] ?? '');
            $slug = (string) ($row['slug'] ?? '');
            $version = (string) ($row['version'] ?? '');
            $status = $this->validStatus((string) ($row['status'] ?? ''));
            $item = $byIdentity[$type . '|' . $slug . '|' . $version] ?? null;
            if ($item === null || $status === '') {
                continue;
            }
            $warning = isset($row['warning']) && is_string($row['warning']) && $row['warning'] !== ''
                ? $row['warning']
                : null;

            $this->cacheModel()->upsert($type, $slug, $version, $item['author'], $status, $warning);
            $results[] = [
                'type'    => $type,
                'slug'    => $slug,
                'version' => $version,
                'status'  => $status,
                'warning' => $warning,
            ];
        }

        return ['ok' => true, 'results' => $results, 'malicious' => $this->extractMaliciousList($data)];
    }

    /**
     * Live-check every installed addon that has no cache row yet. Called from
     * the admin plugins/themes pages so fresh discovery is answered by the
     * time the page renders.
     */
    public function ensureCacheForAll(): void
    {
        $items = $this->collectInstalledAddons();
        if ($items === []) {
            return;
        }

        $cached = $this->statusesForAll();
        $toCheck = [];
        foreach ($items as $item) {
            $key = TrustCache::compositeKey($item['type'], $item['slug'], $item['version'], $item['author']);
            if (!isset($cached[$key])) {
                $toCheck[] = $item;
            }
        }

        if ($toCheck === []) {
            return;
        }

        $outcome = $this->checkAddons($toCheck);
        if ($outcome['ok'] && $outcome['malicious'] !== []) {
            $state = $this->maliciousListRead();
            $this->maliciousListWrite($outcome['malicious'], $state['processed']);
        }
    }

    /**
     * Manual full recheck: every installed addon plus, when a target core
     * release is known, the core release identity in the same batch, then
     * the malicious-list write. The explicit ask overrides the trusted-skip;
     * used by the Updates screen's "Check for updates" action.
     *
     * @return array{ok: bool, results: list<array{type: string, slug: string, version: string, status: string, warning: ?string}>, malicious: list<array{type: string, slug: string}>}
     */
    public function recheckAll(?string $coreVersion = null): array
    {
        $items = $this->collectInstalledAddons();
        if ($coreVersion !== null && $coreVersion !== '') {
            $items[] = $this->coreItem($coreVersion);
        }

        $outcome = $this->checkAddons($items);

        if ($outcome['ok'] && $outcome['malicious'] !== []) {
            $state = $this->maliciousListRead();
            $this->maliciousListWrite($outcome['malicious'], $state['processed']);
        }

        return $outcome;
    }

    // -----------------------------------------------------------------
    // Cache reads
    // -----------------------------------------------------------------

    /**
     * Read the cached answer for one addon. No HTTP, no writes.
     *
     * @return array{status: string, warning: ?string, checked_at: string}|null
     */
    public function getCachedStatus(string $type, string $slug, string $version, string $author): ?array
    {
        $row = $this->cacheModel()->findByAddon($type, $slug, $version, $author);
        if ($row === null) {
            return null;
        }
        return [
            'status'     => (string) $row->status,
            'warning'    => $row->warning,
            'checked_at' => (string) $row->checked_at,
        ];
    }

    /**
     * Every cached answer keyed by composite addon identity.
     *
     * @return array<string, array{status: string, warning: ?string, checked_at: string}>
     */
    public function statusesForAll(): array
    {
        return $this->cacheModel()->statusesForAll();
    }

    // -----------------------------------------------------------------
    // Addon collection
    // -----------------------------------------------------------------

    /**
     * The check identity for one discovered plugin (local or vendor info
     * array from the PluginLoader), or null when the addon is not named
     * properly. Callers share this with collectInstalledAddons() so a
     * single addon and a batch always report the same identity.
     *
     * The manifest's 'name' is the whole identity, 'author/slug' in that
     * order: 'pubvana/blog' means slug 'blog', author 'pubvana'. Composer
     * packages are named the same way by their package name. The install
     * folder never appears in the identity; a plugin whose manifest has
     * no name, or a name without a vendor prefix, has nothing to ask about.
     *
     * @param array<string, mixed> $info PluginLoader discovery info
     * @return array{type: string, slug: string, version: string, author: string, origin: string}|null
     */
    public function pluginItem(string $pluginId, array $info): ?array
    {
        $vendor = ($info['source'] ?? 'local') === 'vendor';

        if ($vendor) {
            $version = (string) ($info['version'] ?? '');
            $name = $pluginId;
        } else {
            $manifest = is_array($info['manifest'] ?? null) ? $info['manifest'] : [];
            $version = (string) ($manifest['semver'] ?? $manifest['version'] ?? '');
            $name = (string) ($manifest['name'] ?? '');
        }

        if ($version === '') {
            return null;
        }

        $identity = $this->splitName($name);
        if ($identity === null) {
            return null;
        }
        [$author, $slug] = $identity;

        return [
            'type'    => self::TYPE_PLUGIN,
            'slug'    => $slug,
            'version' => $version,
            'author'  => $author,
            'origin'  => $vendor ? self::ORIGIN_COMPOSER : self::ORIGIN_LOCAL,
        ];
    }

    /**
     * The check identity for one installed theme, read from its pubvana.json
     * manifest, or null when the manifest does not carry author, slug, and
     * version in full. The folder only locates the manifest; it never
     * enters the identity.
     *
     * @return array{type: string, slug: string, version: string, author: string, origin: string}|null
     */
    public function themeItem(string $folder): ?array
    {
        $manifest = $this->readThemeManifest($folder);
        $version = (string) ($manifest['semver'] ?? $manifest['version'] ?? '');
        $slug = trim((string) ($manifest['slug'] ?? ''));
        $author = trim((string) ($manifest['author'] ?? ''));
        if ($version === '' || $slug === '' || $author === '') {
            return null;
        }

        return [
            'type'    => self::TYPE_THEME,
            'slug'    => $slug,
            'version' => $version,
            'author'  => $author,
            'origin'  => self::ORIGIN_LOCAL,
        ];
    }

    /**
     * The check identity for the Pubvana core release being applied by the
     * Updates plugin. The trust API types everything as plugin or theme, so
     * core reports as the composer-style root package.
     *
     * @return array{type: string, slug: string, version: string, author: string, origin: string}
     */
    public function coreItem(string $version): array
    {
        return [
            'type'    => self::TYPE_PLUGIN,
            'slug'    => 'pubvana/pubvana',
            'version' => $version,
            'author'  => 'pubvana',
            'origin'  => self::ORIGIN_COMPOSER,
        ];
    }

    /**
     * Collect the identity of every installed addon: local plugins (dropped
     * into plugins/), composer packages, and themes.
     *
     * Every identity comes from the addon's declared name and manifest,
     * never from install paths. Entries not named properly (no author/slug
     * name, or a theme manifest without author, slug, and version) are
     * skipped: the API cannot answer for an unnamed addon.
     *
     * @return list<array{type: string, slug: string, version: string, author: string, origin: string}>
     */
    public function collectInstalledAddons(): array
    {
        $addons = [];
        $loader = $this->app->pluginLoader();

        foreach ($loader->discoverLocal() as $pluginId => $info) {
            $item = $this->pluginItem((string) $pluginId, $info);
            if ($item !== null) {
                $addons[] = $item;
            }
        }

        foreach ($loader->discoverVendor() as $pluginId => $info) {
            $item = $this->pluginItem((string) $pluginId, $info);
            if ($item !== null) {
                $addons[] = $item;
            }
        }

        foreach ($this->app->themes()->discover() as $info) {
            $folder = (string) ($info['folder'] ?? '');
            if ($folder === '') {
                continue;
            }
            $item = $this->themeItem($folder);
            if ($item !== null) {
                $addons[] = $item;
            }
        }

        return $addons;
    }

    // -----------------------------------------------------------------
    // Home-site malicious list
    // -----------------------------------------------------------------

    /**
     * Pull the "malicious" list out of a check response. Entries that are
     * malformed, mistyped, or duplicate are dropped; an absent or non-array
     * key means an empty list.
     *
     * @param array<string, mixed> $response
     * @return list<array{type: string, slug: string}>
     */
    public function extractMaliciousList(array $response): array
    {
        $list = $response['malicious'] ?? null;
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $type = (string) ($entry['type'] ?? '');
            $slug = (string) ($entry['slug'] ?? '');
            if (!in_array($type, [self::TYPE_PLUGIN, self::TYPE_THEME], true) || $slug === '') {
                continue;
            }
            $signature = $type . '|' . $slug;
            if (isset($seen[$signature])) {
                continue;
            }
            $seen[$signature] = true;
            $out[] = ['type' => $type, 'slug' => $slug];
        }

        return $out;
    }

    /**
     * Read the malicious-list state file. A missing or corrupt file reads as
     * empty state (fail closed): worst case the findings are re-asked.
     *
     * @return array{current: list<array{type: string, slug: string}>, processed: list<array{type: string, slug: string}>}
     */
    public function maliciousListRead(): array
    {
        if (!is_file($this->maliciousFile)) {
            return ['current' => [], 'processed' => []];
        }

        $raw = file_get_contents($this->maliciousFile);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return ['current' => [], 'processed' => []];
        }

        return [
            'current'   => is_array($data['current'] ?? null) ? $this->extractMaliciousList(['malicious' => $data['current']]) : [],
            'processed' => is_array($data['processed'] ?? null) ? $this->extractMaliciousList(['malicious' => $data['processed']]) : [],
        ];
    }

    /**
     * Write the malicious-list state file. Failures are swallowed: a lost
     * state file costs one repeated re-ask, nothing worse.
     *
     * @param list<array{type: string, slug: string}> $current
     * @param list<array{type: string, slug: string}> $processed
     */
    public function maliciousListWrite(array $current, array $processed): void
    {
        $dir = dirname($this->maliciousFile);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }
        $payload = json_encode(['current' => $current, 'processed' => $processed]);
        if (is_string($payload)) {
            @file_put_contents($this->maliciousFile, $payload, LOCK_EX);
        }
    }

    // -----------------------------------------------------------------
    // Environment
    // -----------------------------------------------------------------

    /**
     * The Pubvana core version, from the root pubvana.json manifest.
     */
    public function getPubvanaVersion(): string
    {
        if ($this->pubvanaVersion !== null) {
            return $this->pubvanaVersion;
        }

        $this->pubvanaVersion = 'unknown';
        $manifestFile = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'pubvana.json';
        if (is_file($manifestFile)) {
            $manifest = json_decode((string) file_get_contents($manifestFile), true);
            if (is_array($manifest)) {
                $semver = $manifest['semver'] ?? null;
                if (is_string($semver) && $semver !== '') {
                    $this->pubvanaVersion = $semver;
                }
            }
        }

        return $this->pubvanaVersion;
    }

    /**
     * This site's base URL: the CMS.siteUrl setting when present, otherwise
     * derived from the request (cron falls back to localhost).
     */
    public function getBaseUrl(): string
    {
        $siteUrl = '';
        try {
            $siteUrl = trim((string) ($this->app->settings()->get('CMS.siteUrl', '') ?? ''));
        } catch (\Throwable) {
            // Settings store unreadable; fall through to the request host.
        }
        if ($siteUrl !== '') {
            return rtrim($siteUrl, '/');
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host;
    }

    /**
     * Whether a successful full batch happened inside the TTL window.
     */
    public function isCacheFresh(): bool
    {
        $last = '';
        try {
            $last = (string) ($this->app->settings()->get(self::LAST_CHECK_KEY, '') ?? '');
        } catch (\Throwable) {
            return false;
        }
        if ($last === '' || strtotime($last) === false) {
            return false;
        }

        return time() < (int) strtotime($last) + (self::CACHE_TTL_HOURS * 3600);
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * POST the items to the check API and decode the response envelope.
     *
     * @param list<array{type: string, slug: string, version: string, author: string, origin?: string}> $items
     * @return array<string, mixed>|null Null on transport failure or a bad envelope
     */
    private function requestCheck(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        $payload = [
            'pv_version' => $this->getPubvanaVersion(),
            'base_url'   => $this->getBaseUrl(),
            'items'      => array_map(static fn (array $item): array => [
                'type'    => $item['type'],
                'slug'    => $item['slug'],
                'version' => $item['version'],
                'author'  => $item['author'],
                'origin'  => $item['origin'] ?? self::ORIGIN_LOCAL,
            ], $items),
        ];

        $body = $this->httpPostJson($this->apiUrl ?? self::TRUST_API_URL, $payload);
        $data = $this->decode($body);

        if (!is_array($data) || !is_array($data['results'] ?? null)) {
            return null;
        }

        return $data;
    }

    /**
     * Reduce the collected addons to the ones worth asking about: everything
     * except rows already cached trusted, unless the home-site list names
     * them (type + slug), in which case they are re-asked on the normal
     * cadence too.
     *
     * @param list<array{type: string, slug: string, version: string, author: string, origin: string}> $items
     * @param list<array{type: string, slug: string}> $maliciousList
     * @return list<array{type: string, slug: string, version: string, author: string, origin: string}>
     */
    private function filterDueItems(array $items, array $maliciousList): array
    {
        $cached = $this->statusesForAll();

        $forced = [];
        foreach ($maliciousList as $entry) {
            $forced[$entry['type'] . '|' . $entry['slug']] = true;
        }

        $due = [];
        foreach ($items as $item) {
            $key = TrustCache::compositeKey($item['type'], $item['slug'], $item['version'], $item['author']);
            $status = $cached[$key]['status'] ?? null;
            if ($status === TrustCache::STATUS_TRUSTED && !isset($forced[$item['type'] . '|' . $item['slug']])) {
                continue;
            }
            $due[] = $item;
        }

        return $due;
    }

    /**
     * Entries of the current list that were not yet processed, keyed by
     * type|slug signature.
     *
     * @param list<array{type: string, slug: string}> $current
     * @param list<array{type: string, slug: string}> $processed
     * @return array<string, array{type: string, slug: string}>
     */
    private function listDiff(array $current, array $processed): array
    {
        $done = [];
        foreach ($processed as $entry) {
            $done[$entry['type'] . '|' . $entry['slug']] = true;
        }

        $new = [];
        foreach ($current as $entry) {
            $signature = $entry['type'] . '|' . $entry['slug'];
            if (!isset($done[$signature])) {
                $new[$signature] = $entry;
            }
        }

        return $new;
    }

    /**
     * Installed addons (full identity) whose type|slug matches one of the
     * given signatures. An entry for something not installed is dropped.
     *
     * @param array<string, array{type: string, slug: string}> $diff
     * @return list<array{type: string, slug: string, version: string, author: string, origin: string}>
     */
    private function matchInstalled(array $diff): array
    {
        $matches = [];
        foreach ($this->collectInstalledAddons() as $item) {
            $signature = $item['type'] . '|' . $item['slug'];
            if (isset($diff[$signature])) {
                $matches[] = $item;
            }
        }

        return $matches;
    }

    /**
     * 'author/slug' in one string ('pubvana/blog'), split to its parts as
     * written. A name without a vendor prefix (no slash), or with an empty
     * half, answers null: the identity is incomplete on the addon's side.
     *
     * @return array{0: string, 1: string}|null [author, slug]
     */
    private function splitName(string $name): ?array
    {
        $name = trim($name);
        $slash = strpos($name, '/');
        if ($slash === false) {
            return null;
        }

        $author = trim(substr($name, 0, $slash));
        $slug = trim(substr($name, $slash + 1));
        if ($author === '' || $slug === '') {
            return null;
        }

        return [$author, $slug];
    }

    /**
     * Read one theme's pubvana.json manifest, empty array when absent or
     * unreadable.
     *
     * @return array<string, mixed>
     */
    private function readThemeManifest(string $folder): array
    {
        $file = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'pubvana.json';
        if (!is_file($file)) {
            return [];
        }
        $manifest = json_decode((string) file_get_contents($file), true);
        return is_array($manifest) ? $manifest : [];
    }

    private function validStatus(string $status): string
    {
        return in_array($status, [
            TrustCache::STATUS_TRUSTED,
            TrustCache::STATUS_KNOWN,
            TrustCache::STATUS_MALICIOUS,
            TrustCache::STATUS_UNKNOWN,
        ], true) ? $status : TrustCache::STATUS_UNKNOWN;
    }

    private function stampLastCheck(): void
    {
        try {
            $this->app->settings()->set(self::LAST_CHECK_KEY, date('c'));
        } catch (\Throwable) {
            // A settings outage must not fail the cron run.
        }
    }

    private function cacheModel(): TrustCache
    {
        return new TrustCache($this->pdo);
    }

    /**
     * JSON-decode a response body, nulling anything that is not an array.
     *
     * @return array<string, mixed>|null
     */
    private function decode(?string $body): ?array
    {
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * The client's identifying user agent at the trust service.
     *
     * @return non-empty-string
     */
    private function userAgent(): string
    {
        return 'Pubvana-TrustClient/3.0';
    }

    /**
     * POST JSON, curl first, stream fallback second. Returns the body only on
     * an HTTP 200 answer. Protected: the HTTP seam across the whole service,
     * test stand-ins override this one method.
     *
     * @param array<string, mixed> $payload
     */
    protected function httpPostJson(string $url, array $payload): ?string
    {
        $data = json_encode($payload) ?: '{}';
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $timeout = self::HTTP_TIMEOUT;

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle !== false) {
                curl_setopt_array($handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $data,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_USERAGENT      => $this->userAgent(),
                ]);
                $body = curl_exec($handle);
                $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                curl_close($handle);
                return is_string($body) && $body !== '' && $status === 200 ? $body : null;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", $headers),
                'content'       => $data,
                'timeout'       => $timeout,
                'ignore_errors' => false,
                'user_agent'    => $this->userAgent(),
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return is_string($body) && $body !== '' ? $body : null;
    }
}
