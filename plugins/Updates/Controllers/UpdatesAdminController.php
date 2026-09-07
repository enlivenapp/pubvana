<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Updates\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Plugins\Updates\Services\UpdateApplyService;
use Pubvana\Plugins\Updates\Services\UpdateProgress;
use Pubvana\Plugins\Updates\Services\UpdateService;
use flight\Engine;
use Throwable;

/**
 * Admin controller for the Updates screen.
 *
 * All logic lives in the services; this controller triggers operations
 * and reports results. Web apply and CLI commands share the exact same
 * service path.
 *
 * @package  Pubvana\Plugins\Updates
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
final class UpdatesAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.updates');
    }

    /**
     * The Updates screen.
     */
    public function index(): void
    {
        if (!$this->guard()) {
            return;
        }

        $service  = $this->service();
        $progress = (new UpdateProgress($service->storageDir()))->read();
        $running  = is_array($progress) && ($progress['status'] ?? '') === 'in_progress';

        $state = $service->lastCheck();

        // Web check trigger: refresh the feed when the cache is stale
        // (bounded by the configured timeout), unless a run is active.
        if (!$running && $service->isDue()) {
            try {
                $state = $service->check(true);
            } catch (Throwable $e) {
                $state['status'] = 'error';
                $state['error']  = $e->getMessage();
            }
        }

        // Web auto-apply trigger: when auto updates are on and an
        // applicable release waits, visiting this page starts the run
        // in the background (once per cache window, never through
        // breaking changes).
        if (!$running
            && $service->autoUpdateEnabled()
            && ($state['status'] ?? '') === 'available'
            && ((array) ($state['breaking_changes'] ?? []) === [])
            && ($state['capped_by'] ?? null) === null
            && $this->execAvailable()
            && !$this->isLocked()
        ) {
            $this->startBackgroundAutoUpdate();
        }

        $targetVersion = (string) ($state['target_version'] ?? '');

        $this->render('pubvana/updates/admin/index', [
            'pageTitle'     => 'Updates',
            'state'         => $state,
            'auto'          => $service->autoUpdateEnabled(),
            'skipped'       => $service->skippedVersions(),
            'preflight'     => $targetVersion !== '' ? $service->preFlight($targetVersion) : [],
            'addons'        => $service->addons(),
            'progress'      => $progress,
            'is_locked'     => $this->isLocked(),
            'changelog_url' => $this->changelogUrl(),
            'adminBase'     => $this->adminBase(),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/updates'), '/');
    }

    /**
     * Force a release check (POST).
     */
    public function check(): void
    {
        if (!$this->guard()) {
            return;
        }

        try {
            $state = $this->service()->check(true);

            if ($state['status'] === 'available') {
                $this->app->session()->flash('info', 'Version ' . $state['target_version'] . ' is available.');
            } elseif ($state['status'] === 'up_to_date') {
                $this->app->session()->flash('success', 'You are running the latest version.');
            } else {
                $this->app->session()->flash('danger', 'Check failed: ' . ($state['error'] ?? 'unknown error'));
            }
        } catch (Throwable $e) {
            $this->app->session()->flash('danger', 'Check failed: ' . $e->getMessage());
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Apply the pending update (POST, AJAX).
     *
     * Prefers a backgrounded runway process; falls back to running
     * synchronously when exec is unavailable.
     */
    public function apply(): void
    {
        if (!$this->guard()) {
            return;
        }

        if ($this->isLocked()) {
            $this->app->json(['status' => 'error', 'message' => 'An update or backup operation is already in progress.']);
            return;
        }

        $data    = $this->app->request()->data->getData();
        $confirm = isset($data['confirm_breaking']);
        $user    = $this->app->auth()->user();
        $by      = is_object($user) && isset($user->username) ? (string) $user->username : 'admin';

        $state  = $this->service()->lastCheck();
        $target = (string) ($state['target_version'] ?? '');

        if ($target === '') {
            $this->app->json(['status' => 'error', 'message' => 'No update is available to apply.']);
            return;
        }

        if ((array) ($state['breaking_changes'] ?? []) !== [] && !$confirm) {
            $this->app->json([
                'status'  => 'confirm_breaking',
                'message' => 'This update path contains breaking changes. Review them and confirm to apply.',
            ]);
            return;
        }

        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s updates:apply --user %s > /dev/null 2>&1 &',
                escapeshellarg(PROJECT_ROOT . '/runway'),
                escapeshellarg($by)
            );
            exec($cmd);

            $this->app->json(['status' => 'started', 'method' => 'exec']);
            return;
        }

        @set_time_limit(600);

        $apply  = new UpdateApplyService($this->app, $this->pluginConfig());
        $result = $apply->apply($target, $by, true);

        if ($result) {
            $this->app->json(['status' => 'completed', 'method' => 'sync']);
        } else {
            $progress = (new UpdateProgress($this->service()->storageDir()))->read();
            $this->app->json([
                'status'  => 'error',
                'method'  => 'sync',
                'message' => (string) ($progress['error'] ?? 'The update failed.'),
            ]);
        }
    }

    /**
     * Poll update progress (GET, AJAX).
     */
    public function status(): void
    {
        $progress = (new UpdateProgress($this->service()->storageDir()))->read();

        $this->app->json($progress ?? ['status' => 'idle']);
    }

    /**
     * Save the auto-update setting (POST, AJAX). The view shows the
     * confirmation inline, v2-style; no redirect.
     */
    public function settings(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = $this->app->request()->data->getData();
        unset($data['_csrf_token']);

        $this->service()->setAutoUpdate(!empty($data['auto_update']));

        $this->app->json(['status' => 'ok', 'message' => 'Update settings saved.']);
    }

    /**
     * Skip the pending target version (POST).
     */
    public function skip(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data    = $this->app->request()->data->getData();
        $version = isset($data['version']) && is_string($data['version']) ? $data['version'] : '';

        if ($version !== '' && preg_match('/^[0-9A-Za-z.\-\+]+$/', $version) === 1) {
            $this->service()->skipVersion($version);
            $this->service()->check(true);
            $this->app->session()->flash('success', 'Version ' . $version . ' skipped. The next applicable release will be offered instead.');
        } else {
            $this->app->session()->flash('danger', 'Invalid version to skip.');
        }

        $this->app->redirect($this->adminBase());
    }

    /**
     * Remove a version from the skip list (POST).
     */
    public function unskip(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data    = $this->app->request()->data->getData();
        $version = isset($data['version']) && is_string($data['version']) ? $data['version'] : '';

        if ($version !== '') {
            $this->service()->unskipVersion($version);
            $this->service()->check(true);
            $this->app->session()->flash('success', 'Version ' . $version . ' will be offered again.');
        }

        $this->app->redirect($this->adminBase());
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function service(): UpdateService
    {
        return $this->app->updates();
    }

    /**
     * @return array<string, mixed>
     */
    private function pluginConfig(): array
    {
        $config = $this->app->get($this->configPrepend);

        return is_array($config) ? $config : [];
    }

    /**
     * Permission gate: flash + redirect, never halt.
     */
    private function guard(): bool
    {
        $user = $this->app->auth()->user();

        if ($user === null || !$user->can('updates.manage')) {
            $this->app->session()->flash('error', 'You do not have permission to manage updates.');
            $this->app->redirect('/admin');
            return false;
        }

        return true;
    }

    private function isLocked(): bool
    {
        return UpdateProgress::isLockedInDir($this->service()->storageDir());
    }

    /**
     * Kick the auto-update chain as a background runway process.
     */
    private function startBackgroundAutoUpdate(): void
    {
        $user = $this->app->auth()->user();
        $by   = is_object($user) && isset($user->username) ? (string) $user->username : 'auto';

        $cmd = sprintf(
            'php %s updates:auto-update --user %s > /dev/null 2>&1 &',
            escapeshellarg(PROJECT_ROOT . '/runway'),
            escapeshellarg($by)
        );
        exec($cmd);
    }

    private function changelogUrl(): string
    {
        return 'https://github.com/Pubvana-CMS/pubvana/blob/main/CHANGELOG.md';
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
