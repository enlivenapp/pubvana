<?php

declare(strict_types=1);

namespace Pubvana\Services;

use flight\Engine;
use Pubvana\Models\Setting;
use Pubvana\Services\ExtensionRegistry;

/**
 * SettingsService - Runtime settings store ($app->settings()).
 *
 * Database-backed settings are the STRONGEST source in the settings
 * precedence chain, superseding config files. Only DECLARED settings
 * participate: anything registered via adext type 'admin.settings'
 * may be stored here; secrets and deployment-level values (DB creds,
 * SESSION_ENCRYPTION_KEY, Shield hmac/jwt keys, APP_ENV, etc.) are
 * never declared, so they can never enter this store or the admin UI.
 *
 * Sole exception: 'Mail.password' (SMTP) is declared and stored, but
 * only ever as ciphertext - the Mailer service encrypts it on write
 * and decrypts it on read, so plaintext credentials never reach the
 * store.
 *
 * Resolution order for get(), strongest first:
 *   1. Database row (this table)
 *   2. The value at $app->get($key) - which already encodes
 *      real-env > .env > config.php thanks to env-overrides.php
 *   3. The declaration's 'default' (from the admin.settings registration)
 *   4. The caller's $default parameter (final safety net)
 *
 * Rows materialize lazily: nothing needs seeding. A row appears the
 * first time a setting is saved through the admin UI or set().
 * Until then values resolve down the chain above.
 *
 * Caching: autoload-flagged rows load in one query on first access;
 * everything else lazy-loads per key on miss. Writes update the cache
 * immediately so reads after writes stay consistent in-request.
 *
 * @package Pubvana\Services
 */
class SettingsService
{
    /**
     * Allowed field types for admin.settings declarations.
     *
     * These map 1:1 onto the inputs rendered by the General settings
     * view. 'array'/'object' storage types remain available to set()
     * programmatically but have no generic input renderer.
     */
    public const FIELD_TYPES = ['text', 'email', 'number', 'textarea', 'select', 'checkbox', 'password'];

    /**
     * Valid key format: namespaced dot notation. First segment is the
     * owning namespace (plugin or core feature), at least one dot.
     *
     * @var string
     */
    protected const KEY_PATTERN = '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_.]+$/';

    /** @var Engine<object> Flight application instance */
    protected Engine $app;

    /**
     * In-memory cache of DB rows.
     * Format: key => ['value' => ?string, 'type' => string]
     *
     * @var array<string, array{value: ?string, type: string}>
     */
    protected array $rows = [];

    /** @var bool Whether the boot-time autoload query has run */
    protected bool $autoloadLoaded = false;

    /** @var bool True when the bulk load failed (table missing); keeps per-key fallback reads alive */
    protected bool $bulkLoadFailed = false;

    /**
     * Per-request register of keys confirmed absent from the bulk autoload.
     * Prevents a per-key SELECT on every request for prompts the codebase
     * asks for but the database never has rows for (e.g. CMS.siteByline,
     * CMS.logo, CMS.favicon, CMS.copyright, the comments service's setting
     * lookup). Massive no-op reads, single round-trip, dies with the request.
     *
     * @var array<string, true>
     */
    protected array $negativeCache = [];

    /**
     * Normalized declaration cache, built once per request.
     * Format: key => normalized field definition
     *
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $fieldDeclarations = null;

    /**
     * @param Engine<object> $app Flight application for db(), adext() and $app->get()
     */
    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    // -----------------------------------------------------------------
    // Reads
    // -----------------------------------------------------------------

    /**
     * Get a setting's resolved value.
     *
     * Resolution order: DB row > the app's value (env > .env > config.php,
     * pre-applied by env-overrides.php) > declaration default >
     * caller's $default. Returns null when nothing resolves.
     *
     * @param string $key     Namespaced key (e.g. 'CMS.siteName')
     * @param mixed  $default Final fallback when nothing resolves
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 1. Database row wins outright
        $row = $this->row($key);
        if ($row !== null) {
            return Setting::cast($row['type'], $row['value']);
        }

        // 2. The app's value here: env vars/.env/config.php already layered in.
        //    A declaration may point elsewhere via 'fallback'.
        $declaration = $this->declaredFields()[$key] ?? null;
        $fallbackKey = $declaration['fallback'] ?? $key;
        $stored = $this->app->get($fallbackKey);
        if ($stored !== null) {
            return $stored;
        }

        // 3. Declared default beats the caller's argument: the
        //    declaration is the setting's documented default.
        if ($declaration !== null && array_key_exists('default', $declaration)) {
            return $declaration['default'];
        }

        // 4. Caller safety net
        return $default;
    }

    /**
     * Does a value resolve for this key?
     *
     * True when a DB row exists OR any tier below resolves to a
     * non-null value. Note: an intentionally-stored NULL row counts
     * as having a value (the row IS the value).
     *
     * @param string $key Namespaced key
     */
    public function has(string $key): bool
    {
        if ($this->row($key) !== null) {
            return true;
        }
        return $this->get($key) !== null;
    }

    /**
     * Get every setting in one namespace, merged across tiers.
     *
     * Merge order (weakest to strongest): declaration defaults <
     * the app's value (env/.env/config.php) < database rows.
     *
     * @param string $namespace Namespace before the dot (e.g. 'CMS', 'blog')
     * @return array<string, mixed>
     */
    public function all(string $namespace): array
    {
        $prefix   = $namespace . '.';
        $fields   = $this->declaredFields();
        $this->ensureAutoloadLoaded();

        // Candidate keys: everything declared in the namespace plus
        // anything physically stored in it (rows may outlive a
        // plugin's declarations).
        $keys = [];
        foreach ($fields as $key => $field) {
            if (str_starts_with($key, $prefix)) {
                $keys[] = $key;
            }
        }
        foreach ($this->rows as $key => $row) {
            if (str_starts_with($key, $prefix)) {
                $keys[] = $key;
            }
        }

        // Resolve each candidate down the chain: DB row > the app's value
        // (env/.env/config.php) > declaration default. Keys that
        // resolve nowhere are omitted.
        $merged = [];
        foreach (array_unique($keys) as $key) {
            if (isset($this->rows[$key])) {
                $merged[$key] = Setting::cast($this->rows[$key]['type'], $this->rows[$key]['value']);
                continue;
            }

            $stored = $this->app->get($fields[$key]['fallback'] ?? $key);
            if ($stored !== null) {
                $merged[$key] = $stored;
                continue;
            }

            if (isset($fields[$key]) && array_key_exists('default', $fields[$key])) {
                $merged[$key] = $fields[$key]['default'];
            }
        }

        return $merged;
    }

    // -----------------------------------------------------------------
    // Writes
    // -----------------------------------------------------------------

    /**
     * Store a setting, creating or updating its row.
     *
     * Values keep their PHP types: scalars are stored human-readable
     * with the type recorded for cast-back; arrays/objects are
     * JSON-encoded. The in-memory cache updates immediately.
     *
     * @param string $key   Namespaced key (e.g. 'blog.postsPerPage')
     * @param mixed  $value Value to store (null stores a NULL row)
     * @throws \InvalidArgumentException On malformed keys
     */
    public function set(string $key, mixed $value): void
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException(
                "Settings: invalid key '{$key}'. Keys must be namespaced dot notation: 'namespace.key'"
            );
        }

        $this->ensureAutoloadLoaded();

        $encoded = $this->encode($value);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $autoload = $this->declaredFields()[$key]['autoload'] ?? true;

        $model = new Setting($this->app->db());
        $existing = $model->findByKey($key);

        if ($existing === null) {
            $model = new Setting($this->app->db());
            $model->key = $key;
            $model->value = $encoded['value'];
            $model->type = $encoded['type'];
            $model->autoload = $autoload;
            $model->created_at = $now;
            $model->updated_at = $now;
            $model->insert();
        } else {
            $model = $existing;
            $model->value = $encoded['value'];
            $model->type = $encoded['type'];
            $model->autoload = $autoload;
            $model->updated_at = $now;
            $model->update();
        }

        $this->rows[$key] = $encoded;
        // The value is now readable: don't let a stale negative-cache entry
        // (set when this key previously missed) mask the fresh write.
        unset($this->negativeCache[$key]);
    }

    /**
     * Delete a setting's row, falling back down the resolution chain.
     *
     * @param string $key Namespaced key
     */
    public function forget(string $key): void
    {
        $this->ensureAutoloadLoaded();

        $model = new Setting($this->app->db());
        $model->eq('key', $key)->delete();

        unset($this->rows[$key]);
    }

    // -----------------------------------------------------------------
    // Declarations (adext 'admin.settings')
    // -----------------------------------------------------------------

    /**
     * All validated setting field declarations, indexed by key.
     *
     * Gathers every contribution across the 'admin.settings' type's
     * slots, validates each field definition, and normalizes it.
     * Invalid fields are skipped with a logged reason - one broken
     * plugin field must not break the whole settings page.
     *
     * @return array<string, array<string, mixed>>
     */
    public function declaredFields(): array
    {
        if ($this->fieldDeclarations !== null && $this->fieldDeclarations !== []) {
            return $this->fieldDeclarations;
        }

        $this->fieldDeclarations = [];

        $slots = ExtensionRegistry::TYPES['admin.settings']['slots'];
        foreach ($slots as $slot) {
            foreach ($this->app->adext()->get('admin.settings', $slot) as $contributor => $tab) {
                foreach ($tab['fields'] ?? [] as $index => $field) {
                    $error = $this->validateField($field, "{$contributor}#{$index}");
                    if ($error !== null) {
                        error_log("SettingsService: skipping field - {$error}");
                        continue;
                    }
                    $key = $field['key'];
                    if (isset($this->fieldDeclarations[$key])) {
                        error_log("SettingsService: duplicate declaration for '{$key}' from '{$contributor}' - first wins");
                        continue;
                    }
                    $this->fieldDeclarations[$key] = $field;
                }
            }
        }

        // An empty scan is not memoized (guard above skips empty), so the
        // rescan repeats until declarations register during boot.
        return $this->fieldDeclarations;
    }

    /**
     * Validate one declared field. Returns an error message, or null
     * when the field is valid. Normalizes in place where safe.
     *
     * @param array<string, mixed> $field  Raw field definition
     * @param string               $source Contributor label for error messages
     */
    protected function validateField(array &$field, string $source): ?string
    {
        foreach (['key', 'label', 'type'] as $required) {
            if (!isset($field[$required]) || $field[$required] === '') {
                return "'{$source}' is missing required key '{$required}'";
            }
        }

        if (preg_match(self::KEY_PATTERN, (string) $field['key']) !== 1) {
            return "'{$source}' key '{$field['key']}' must be namespaced dot notation";
        }

        if (!in_array($field['type'], self::FIELD_TYPES, true)) {
            $allowed = implode(', ', self::FIELD_TYPES);
            return "'{$source}' has unknown type '{$field['type']}'. Allowed: {$allowed}";
        }

        if ($field['type'] === 'select') {
            // Select options may be declared empty and filled lazily by the
            // consuming controller at render/save time (see
            // SettingsController::resolveOptions for CMS.homepagePageId).
            // Save-time validation still rejects any value not in the
            // resolved options via SettingsController::coerce, so an empty
            // declaration here is not a data-integrity hole.
            $field['options'] = (array) ($field['options'] ?? []);
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Internal Helpers
    // -----------------------------------------------------------------

    /**
     * Fetch a raw cached/stored row, lazy-loading on cache miss.
     *
     * Negative results are cached too: PublicController's buildGlobalData()
     * reads four CMS.* keys at request time. Without negative caching, each
     * miss becomes a round-trip even after the bulk autoload runs. We mark
     * a known-absent key as a sentinel that the next $this->rows hit requires.
     *
     * @param string $key Namespaced key
     * @return array{value: ?string, type: string}|null
     */
    protected function row(string $key): ?array
    {
        $this->ensureAutoloadLoaded();

        if (isset($this->negativeCache[$key])) {
            return null;
        }
        if (isset($this->rows[$key])) {
            return $this->rows[$key];
        }

        // The bulk load snapshots the entire settings table, so a key not
        // in it has no row. No per-key probe needed. Only when the bulk
        // query failed (fresh install, table not migrated yet) do we fall
        // back to the per-key read.
        if ($this->bulkLoadFailed) {
            try {
                $found = (new Setting($this->app->db()))->findByKey($key);
            } catch (\Throwable $e) {
                error_log('SettingsService: unable to read "' . $key . '" (' . $e->getMessage() . ')');
                return null;
            }

            if ($found === null) {
                $this->negativeCache[$key] = true;
                return null;
            }

            $this->rows[$key] = [
                'value' => $found->value,
                'type'  => $found->type,
            ];
            return $this->rows[$key];
        }

        $this->negativeCache[$key] = true;
        return null;
    }

    /**
     * Run the boot-time bulk query once (autoload rows only).
     *
     * Failure tolerant: on a fresh install the table may not exist yet
     * (migrations run later during plugin loading). We log and start
     * empty rather than breaking boot.
     */
    protected function ensureAutoloadLoaded(): void
    {
        if ($this->autoloadLoaded) {
            return;
        }
        $this->autoloadLoaded = true;

        try {
            foreach ((new Setting($this->app->db()))->getAutoloadRows() as $row) {
                $this->rows[$row->key] = [
                    'value' => $row->value,
                    'type'  => $row->type,
                ];
            }
        } catch (\Throwable $e) {
            $this->bulkLoadFailed = true;
            error_log('SettingsService: unable to load settings (' . $e->getMessage() . ')');
        }
    }

    /**
     * Encode a PHP value for storage.
     *
     * Scalars stay human-readable; arrays/objects JSON-encode. The
     * returned 'type' uses gettype() vocabulary so the model's cast()
     * round-trips exactly.
     *
     * @param mixed $value
     * @return array{value: ?string, type: string}
     */
    protected function encode(mixed $value): array
    {
        $type = gettype($value);

        return match ($type) {
            'NULL'    => ['value' => null, 'type' => 'NULL'],
            'boolean' => ['value' => $value ? '1' : '0', 'type' => 'boolean'],
            'integer' => ['value' => (string) $value, 'type' => 'integer'],
            'double'  => ['value' => (string) $value, 'type' => 'double'],
            'array',
            'object'  => ['value' => json_encode($value) ?: '{}', 'type' => $type],
            default   => ['value' => (string) $value, 'type' => 'string'],
        };
    }
}
