<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

use Pubvana\Models\TrustCache;
use flight\Engine;

/**
 * PluginsController - Plugin management page (Plugins > Manage).
 *
 * Lists every discovered plugin (local + vendor) with its database-backed
 * state. The admin can enable/disable a plugin and change its load priority.
 *
 * THE PAUSE: a newly installed plugin's row defaults to enabled=false
 * (see PluginLoader::defaultState). Its migrations, seeds, and registration
 * code never run until this page flips it on — and enabling runs that
 * plugin's migrations SEEDS IMMEDIATELY in the same request. If any
 * migration fails, the plugin is left DISABLED (row reverted) and the
 * error is surfaced here. A future boot after a half-failed enable is
 * therefore never bricked by the change.
 *
 * Required plugins (sessions/shield/csrf) are locked: they cannot be
 * disabled by anyone, and their priority is fixed.
 *
 * Security: this controller is only mounted for `plugins.manage` holders.
 * Superadmin bypasses via the bulk permission grant in Shield.
 *
 * @package Pubvana\Controllers\Admin
 */
class PluginsController extends AdminController
{
    /**
     * @param Engine<object> $app
     */
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana');
    }

    /**
     * Show the Plugins management page.
     *
     * Before rendering, any discovered addon with no cache row is checked
     * against the trust service live (one batch), so a fresh install shows
     * real answers instead of empty badges. Cache reads are wrapped: a trust
     * service outage degrades the page to 'no data' badges, never an error.
     */
    public function index(): void
    {
        $loader = $this->app->pluginLoader();
        $plugins = array_merge($loader->discoverLocal(), $loader->discoverVendor());

        $statuses = [];
        try {
            $this->app->trustClient()->ensureCacheForAll();
            $statuses = $this->app->trustClient()->statusesForAll();
        } catch (\Throwable) {
            $statuses = [];
        }

        $rows = [];
        $maliciousActive = [];
        foreach ($plugins as $id => $info) {
            $state = $loader->getPluginState($id);
            $enabled = $state !== null ? (bool) $state['enabled'] : $loader->isEnabled($id);

            $trust = ['status' => 'none', 'warning' => null];
            $item = $this->trustItem((string) $id, $info);
            if ($item !== null) {
                $cached = $statuses[TrustCache::compositeKey(
                    $item['type'],
                    $item['slug'],
                    $item['version'],
                    $item['author']
                )] ?? null;
                if ($cached !== null) {
                    $trust = ['status' => $cached['status'], 'warning' => $cached['warning']];
                }
            }

            $rows[] = [
                'id'            => $id,
                'source'        => ($info['source'] ?? 'local') === 'local' ? 'local' : 'vendor',
                'name'          => $info['name'] ?? $id,
                'version'       => $info['version'] ?? '',
                'enabled'       => $enabled,
                'priority'      => $state !== null ? (int) $state['priority'] : 50,
                'required'      => $state !== null ? (bool) $state['required'] : $loader->isRequired($id),
                'trust_status'  => $trust['status'],
                'trust_warning' => $trust['warning'],
            ];

            if ($enabled && $trust['status'] === TrustCache::STATUS_MALICIOUS) {
                $maliciousActive[] = [
                    'name'    => (string) ($info['name'] ?? $id),
                    'warning' => $trust['warning'],
                ];
            }
        }

        // Required plugins first, then by priority, then plugin name
        usort($rows, static fn ($a, $b): int => $b['required'] <=> $a['required']
            ?: $a['priority'] <=> $b['priority']
            ?: strcmp($a['name'], $b['name']));

        $this->render('admin/plugins/index', [
            'pageTitle'       => 'Plugins',
            'plugins'         => $rows,
            'maliciousActive' => $maliciousActive,
            'flash'           => $this->app->session()->pullFlash('plugins_flash'),
        ]);
    }

    /**
     * Save the enabled/disabled state for posted plugins.
     *
     * Autosave: each row posts its own tiny form (toggle only). Priority is
     * read-only and never written here — plugin_state stays authoritative.
     *
     * - Only posted plugin IDs that were actually discovered are accepted.
     * - Required plugins are never disabled (server-side invariant).
     * - Transitioning a plugin enabled runs its pending migrations + seeds
     *   immediately. On failure the plugin row is reverted to disabled and the
     *   error is flashed.
     * - Trust gate: an enable first consults the Pubvana trust service. The
     *   AJAX path answers trusted (proceed), unknown (needsConfirm JSON for
     *   the confirmation modal), or malicious (blocked JSON, refusal). A
     *   confirmed resubmit carries force=1, which skips the live call but
     *   still refuses a cached malicious verdict. The non-AJAX fallback has
     *   no modal, so only a cached malicious verdict is a hard stop there.
     */
    public function save(): void
    {
        $loader = $this->app->pluginLoader();
        $data = $this->app->request()->data->getData();
        $post = (array) ($data['plugins'] ?? []);
        $known = array_merge($loader->discoverLocal(), $loader->discoverVendor());

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        $force = !empty($data['force']);

        $changed = 0;
        $failures = [];

        // Only act on plugins the form explicitly posted. A row absent from
        // the POST (or an unknown plugin ID) is never touched — it does NOT
        // mean "disable" during a partial save.
        $posted = array_intersect_key($post, $known);

        foreach ($posted as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $info = $known[$id];

            // Hidden 'enabled=0' + checkbox 'enabled=1' → PHP keeps the last
            // value; absence of the key entirely means the row wasn't posted.
            $wanted = (bool) ($row['enabled'] ?? null);

            $state = (new \Pubvana\Models\PluginState($this->app->db()))->findByPluginId($id);
            if ($state === null) {
                continue;
            }

            // Priority is read-only (data, not form control) — this save only
            // ever flips the enabled switch. DB/plugin_state stays authoritative.
            $wasEnabled = (bool) $state->enabled;
            if ($state->required) {
                // Safety invariant: required plugins cannot be disabled.
                $wanted = true;
            }

            if ($wanted === $wasEnabled) {
                continue;
            }

            if (!$wasEnabled && $wanted) {
                $refusal = $this->trustVerdict((string) $id, $info, $force, $isAjax);
                if ($refusal !== null) {
                    $failures[$id] = $refusal;
                    continue;
                }

                // The moment of the pause. Run THIS plugin's migrations +
                // seeds NOW. If anything fails, leave it disabled.
                [$paths, $seeds] = $loader->pluginMigrationPatterns($id, $info);
                if ($paths === []) {
                    // No migrations needed - safe to flip.
                    $state->enabled = true;
                    $state->updated_at = date('Y-m-d H:i:s');
                    $state->save();
                    $changed++;
                    continue;
                }

                try {
                    $migrate = new \Enlivenapp\Migrations\Services\MigrationSetup($this->app->db(), [
                        'migrations' => ['paths' => $paths, 'seeds' => ['paths' => $seeds]],
                    ]);
                    $result = $migrate->runMigrate();
                    $failed = false;
                    foreach ($result as $moduleResult) {
                        if ($moduleResult->hasMigrationFailure()) {
                            $failed = true;
                            break;
                        }
                    }
                    if ($failed) {
                        $failures[$id] = 'left disabled: migration failed';
                        continue; // stay disabled
                    }

                    $state->enabled = true;
                    $state->updated_at = date('Y-m-d H:i:s');
                    $state->save();
                    $changed++;
                } catch (\Throwable $e) {
                    $failures[$id] = 'left disabled: ' . $e->getMessage();
                    continue; // stay disabled
                }
                continue;
            }

            // Simple toggle (disable)
            $state->enabled = $wanted;
            $state->updated_at = date('Y-m-d H:i:s');
            $state->save();
            $changed++;
        }

        $message = 'No changes to apply.';
        if ($changed > 0) {
            $message = $changed === 1 ? '1 plugin updated.' : "{$changed} plugins updated.";
        }

        foreach ($failures as $id => $error) {
            $message .= " '{$id}' " . $error;
        }

        // AJAX (the toggle forms) gets JSON and reloads on the client side;
        // flash first so the fresh page still reports the outcome.
        if ($isAjax) {
            if ($changed > 0 || $failures !== []) {
                $this->app->session()->flash('plugins_flash', $message);
            }
            $this->app->jsonHalt(['ok' => true, 'message' => $message]);
        }

        $this->app->session()->flash('plugins_flash', $message);
        $this->app->redirect('/admin/plugins');
    }

    /**
     * Manual recheck of one plugin (AJAX). Re-derives the addon's identity
     * from discovery, asks the trust service live, updates the cache, and
     * answers with the fresh status so the page can update the badge.
     */
    public function recheck(): void
    {
        $pluginId = (string) ($this->app->request()->data->getData()['plugin'] ?? '');
        $loader = $this->app->pluginLoader();
        $known = array_merge($loader->discoverLocal(), $loader->discoverVendor());

        if ($pluginId === '' || !isset($known[$pluginId])) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'Unknown plugin.'], 404);
        }

        $item = $this->trustItem($pluginId, $known[$pluginId]);
        if ($item === null) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'This plugin has no version to check.'], 422);
        }

        try {
            $result = $this->app->trustClient()->checkAddon(
                $item['type'],
                $item['slug'],
                $item['version'],
                $item['author'],
                $item['origin']
            );
        } catch (\Throwable) {
            $result = ['status' => TrustCache::STATUS_UNKNOWN, 'warning' => null, 'answered' => false];
        }

        if (!$result['answered']) {
            $this->app->jsonHalt(['ok' => false, 'error' => 'The trust service could not be reached.'], 503);
        }

        $this->app->jsonHalt([
            'ok'      => true,
            'status'  => $result['status'],
            'warning' => $result['warning'],
        ]);
    }

    /**
     * The check identity for one discovered plugin, or null when the trust
     * layer is unavailable or the addon has nothing to check.
     *
     * @param array<string, mixed> $info
     * @return array{type: string, slug: string, version: string, author: string, origin: string}|null
     */
    private function trustItem(string $id, array $info): ?array
    {
        try {
            return $this->app->trustClient()->pluginItem($id, $info);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The trust verdict for an enable transition, via the shared admin gate.
     *
     * @param array<string, mixed> $info
     */
    private function trustVerdict(string $id, array $info, bool $force, bool $isAjax): ?string
    {
        $item = $this->trustItem($id, $info);
        if ($item === null) {
            return null;
        }

        $payload = [
            'id'      => $id,
            'name'    => (string) ($info['name'] ?? $id),
            'version' => $item['version'],
        ];

        return $this->trustGate($item, 'plugin', $payload, $force, $isAjax);
    }
}
