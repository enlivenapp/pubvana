<?php

namespace App\Controllers\Admin;

use App\Services\ActivityLogger;
use App\Services\BackupService;

class Backup extends BaseAdminController
{
    public function index(): string
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $service = new BackupService();

        return $this->adminView('backup/index', array_merge(
            $this->baseData('Backup & Export', 'backup'),
            ['backups' => $service->listBackups()]
        ));
    }

    public function download()
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        try {
            $service = new BackupService();
            $zipPath = $service->createBackup();
        } catch (\Throwable $e) {
            log_message('error', 'Backup failed: ' . $e->getMessage());
            return redirect()->to('/admin/backup')->with('error', 'Backup failed: ' . $e->getMessage());
        }

        ActivityLogger::log('settings.updated', 'setting', null, 'Created site backup');

        $filename = basename($zipPath);
        $size     = filesize($zipPath);

        // Stream the zip then delete it
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $size);
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    public function deleteFile()
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $filename = $this->request->getPost('filename') ?? '';
        $service  = new BackupService();

        if ($service->deleteBackup($filename)) {
            return redirect()->to('/admin/backup')->with('success', 'Backup deleted.');
        }

        return redirect()->to('/admin/backup')->with('error', 'Could not delete backup.');
    }
}
