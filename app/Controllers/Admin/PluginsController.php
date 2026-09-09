<?php

declare(strict_types=1);

namespace Pubvana\Controllers\Admin;

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
     */
    public function index(): void
    {
        $loader = $this->app->pluginLoader();
        $plugins = array_merge($loader->discoverLocal(), $loader->discoverVendor());

        $rows = [];
        foreach ($plugins as $id => $info) {
            $state = $loader->getPluginState($id);
            $rows[] = [
                'id'       => $id,
                'source'   => ($info['source'] ?? 'local') === 'local' ? 'local' : 'vendor',
                'name'     => $info['name'] ?? $id,
                'version'  => $info['version'] ?? '',
                'enabled'  => $state !== null ? (bool) $state['enabled'] : $loader->isEnabled($id),
                'priority' => $state !== null ? (int) $state['priority'] : 50,
                'required' => $state !== null ? (bool) $state['required'] : $loader->isRequired($id),
            ];
        }

        // Required plugins first, then by priority, then plugin name
        usort($rows, static fn ($a, $b): int => $b['required'] <=> $a['required']
            ?: $a['priority'] <=> $b['priority']
            ?: strcmp($a['name'], $b['name']));

        $this->render('admin/plugins/index', [
            'pageTitle' => 'Plugins',
            'plugins'   => $rows,
            'flash'    => $this->app->session()->pullFlash('plugins_flash'),
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
     */
    public function save(): void
    {
        $loader = $this->app->pluginLoader();
        $post = (array) ($this->app->request()->data->getData()['plugins'] ?? []);
        $known = array_merge($loader->discoverLocal(), $loader->discoverVendor());

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
                        $failures[$id] = 'Migration failed';
                        continue; // stay disabled
                    }

                    $state->enabled = true;
                    $state->updated_at = date('Y-m-d H:i:s');
                    $state->save();
                    $changed++;
                } catch (\Throwable $e) {
                    $failures[$id] = $e->getMessage();
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
            $message .= " '{$id}' left disabled — " . $error;
        }

        $this->app->session()->flash('plugins_flash', $message);
        $this->app->redirect('/admin/plugins');
    }
}
