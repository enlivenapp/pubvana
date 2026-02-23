<?php

namespace App\Controllers\Admin;

use App\Services\WordPressImportService;

class Import extends BaseAdminController
{
    public function index(): string
    {
        return $this->adminView('import/index', $this->baseData('Import', 'import'));
    }

    public function upload()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $file = $this->request->getFile('wxr_file');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Please upload a valid WordPress WXR export file.');
        }

        $ext = strtolower($file->getClientExtension());
        if (! in_array($ext, ['xml'], true)) {
            return redirect()->back()->with('error', 'Only .xml files are accepted.');
        }

        if ($file->getSize() > 50 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Import file too large. Maximum size is 50 MB.');
        }

        $tmpDir = WRITEPATH . 'tmp/';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $tmpName = 'wp_import_' . time() . '.xml';
        $file->move($tmpDir, $tmpName);
        $tmpPath = $tmpDir . $tmpName;

        $dryRun = (bool) $this->request->getPost('dry_run');

        $service = new WordPressImportService();
        $service->setDryRun($dryRun);
        $results = $service->import($tmpPath);

        @unlink($tmpPath);

        return $this->adminView('import/index', array_merge($this->baseData('Import', 'import'), [
            'results' => $results,
            'dry_run' => $dryRun,
        ]));
    }
}
