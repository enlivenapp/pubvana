<?php

declare(strict_types=1);

namespace Pubvana\Plugins\Backups\Controllers;

use Pubvana\Controllers\Admin\AdminController;
use Pubvana\Plugins\Backups\Services\BackupService;
use Pubvana\Plugins\Backups\Services\ProgressReporter;
use Pubvana\Plugins\Backups\Services\RestoreService;
use flight\Engine;

/**
 * Admin controller for backup management.
 *
 * @package  Pubvana\Plugins\Backups
 * @copyright 2026 enlivenapp
 * @license  MIT
 */
class BackupsAdminController extends AdminController
{
    public function __construct(Engine $app)
    {
        parent::__construct($app, 'pubvana.backups');
    }

    /**
     * Get the BackupService from the app container.
     */
    protected function service(): BackupService
    {
        return $this->app->backups();
    }

    /**
     * Get the plugin config array.
     *
     * @return array<string, mixed>
     */
    protected function pluginConfig(): array
    {
        return $this->app->get($this->configPrepend) ?? [];
    }

    /**
     * Get the backup storage directory for progress files.
     */
    protected function storageDir(): string
    {
        return $this->service()->getBackupDir();
    }

    /**
     * Backup listing page.
     */
    public function index(): void
    {
        $this->render('pubvana/backups/admin/index', [
            'pageTitle' => 'Backups',
            'backups'   => $this->service()->listBackups(),
            'is_locked' => ProgressReporter::isLocked($this->storageDir()),
            'adminBase' => $this->adminBase(),
        ]);
    }

    /**
     * Full admin URL base for this plugin (adext prepends '/admin' to the
     * registered route path).
     */
    private function adminBase(): string
    {
        return '/admin' . rtrim((string) $this->app->pluginLoader()->routePrefix('pubvana/backups'), '/');
    }

    /**
     * Create a new backup (AJAX).
     *
     * Tries background exec first, falls back to synchronous inline.
     */
    public function create(): void
    {
        if (ProgressReporter::isLocked($this->storageDir())) {
            $this->app->json(['status' => 'error', 'message' => 'Another operation is already in progress.']);
            return;
        }

        $user        = $this->app->auth()->user();
        $triggeredBy = $user->username ?? 'unknown';

        // Try exec (background process via runway)
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s backups:create --trigger manual --user %s > /dev/null 2>&1 &',
                escapeshellarg(PROJECT_ROOT . '/runway'),
                escapeshellarg($triggeredBy)
            );
            exec($cmd);
            $this->app->json(['status' => 'started', 'method' => 'exec']);
            return;
        }

        // Synchronous fallback
        @set_time_limit(300);
        try {
            $reporter = new ProgressReporter('backup', $this->storageDir());
            if (!$reporter->acquireLock()) {
                $this->app->json(['status' => 'error', 'message' => 'Another operation is already in progress.']);
                return;
            }

            $zipPath = $this->service()->createBackup('manual', $triggeredBy, function (int $step, int $total, string $label) use ($reporter) {
                $reporter->update($step, $total, $label);
            });

            $reporter->complete(['detail' => basename($zipPath)]);
            $reporter->releaseLock();

            $this->app->json(['status' => 'completed', 'message' => 'Backup created: ' . basename($zipPath)]);
        } catch (\Throwable $e) {
            if (isset($reporter)) {
                $reporter->error('backup', $e->getMessage());
                $reporter->releaseLock();
            }
            $this->app->json(['status' => 'error', 'message' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a backup zip.
     */
    public function download(string $filename): void
    {
        $path = $this->service()->getBackupPath($filename);
        if (!$path) {
            $this->app->redirect($this->adminBase());
            return;
        }

        $response = $this->app->response();
        $response->header('Content-Type', 'application/zip');
        $response->header('Content-Disposition', 'attachment; filename="' . basename($path) . '"');
        $response->header('Content-Length', (string) filesize($path));
        $content = file_get_contents($path);
        if ($content !== false) {
            $response->write($content);
        }
        $response->send();
    }

    /**
     * Delete a backup zip.
     */
    public function delete(string $filename): void
    {
        if ($this->service()->deleteBackup($filename)) {
            $this->app->session()->flash('success', 'Backup deleted.');
        } else {
            $this->app->session()->flash('error', 'Backup not found.');
        }
        $this->app->redirect($this->adminBase());
    }

    /**
     * Restore from a backup (AJAX).
     */
    public function restore(string $filename): void
    {
        if (ProgressReporter::isLocked($this->storageDir())) {
            $this->app->json(['status' => 'error', 'message' => 'Another operation is already in progress.']);
            return;
        }

        $path = $this->service()->getBackupPath($filename);
        if (!$path) {
            $this->app->json(['status' => 'error', 'message' => 'Backup not found.']);
            return;
        }

        $user        = $this->app->auth()->user();
        $triggeredBy = $user->username ?? 'unknown';

        // Try exec (background process via runway)
        if ($this->execAvailable()) {
            $cmd = sprintf(
                'php %s backups:restore %s --user %s > /dev/null 2>&1 &',
                escapeshellarg(PROJECT_ROOT . '/runway'),
                escapeshellarg($filename),
                escapeshellarg($triggeredBy)
            );
            exec($cmd);
            $this->app->json(['status' => 'started', 'method' => 'exec']);
            return;
        }

        // Synchronous fallback
        @set_time_limit(300);
        try {
            $reporter = new ProgressReporter('rollback', $this->storageDir());
            if (!$reporter->acquireLock()) {
                $this->app->json(['status' => 'error', 'message' => 'Another operation is already in progress.']);
                return;
            }

            $config         = $this->pluginConfig();
            $restoreService = new RestoreService($this->service(), $config);
            $restoreService->restore($filename, $triggeredBy, function (int $step, int $total, string $label) use ($reporter) {
                $reporter->update($step, $total, $label);
            });

            $reporter->complete();
            $reporter->releaseLock();

            $this->app->json(['status' => 'completed', 'message' => 'Restore complete.']);
        } catch (\Throwable $e) {
            if (isset($reporter)) {
                $reporter->error('rollback', $e->getMessage());
                $reporter->releaseLock();
            }
            $this->app->json(['status' => 'error', 'message' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Poll backup/restore progress (AJAX).
     */
    public function status(): void
    {
        $reporter = new ProgressReporter('backup', $this->storageDir());
        $data = $reporter->read();

        // Also check rollback progress
        if ($data === null || ($data['status'] ?? '') === 'idle') {
            $rollbackReporter = new ProgressReporter('rollback', $this->storageDir());
            $rollbackData = $rollbackReporter->read();
            if ($rollbackData !== null) {
                $data = $rollbackData;
            }
        }

        $this->app->json($data ?? ['status' => 'idle']);
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