<?php

namespace App\Controllers\Admin;

use App\Models\ActivityLogModel;

class ActivityLog extends BaseAdminController
{
    public function index(): string
    {
        $this->requirePremium();

        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }

        $type  = $this->request->getGet('type') ?? '';
        $valid = ['', 'post', 'page', 'user', 'theme', 'setting', 'marketplace'];
        if (! in_array($type, $valid, true)) {
            $type = '';
        }

        $model   = new ActivityLogModel();
        $entries = $model->getRecent(20, $type);

        return $this->adminView('activity_log/index', array_merge(
            $this->baseData('Activity Log', 'activity_log'),
            [
                'entries' => $entries,
                'pager'   => $model->pager,
                'type'    => $type,
            ]
        ));
    }
}
