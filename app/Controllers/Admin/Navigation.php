<?php

namespace App\Controllers\Admin;

use App\Models\NavigationModel;

class Navigation extends BaseAdminController
{
    public function index(): string
    {
        if (! auth()->user()->can('admin.navigation')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $model = new NavigationModel();
        $group = $this->request->getGet('group') ?? 'primary';
        if (! in_array($group, ['primary', 'footer'], true)) {
            $group = 'primary';
        }
        return $this->adminView('navigation/index', array_merge($this->baseData('Navigation', 'navigation'), [
            'items' => $model->where('nav_group', $group)->orderBy('sort_order')->findAll(),
            'group' => $group,
        ]));
    }

    public function store()
    {
        if (! auth()->user()->can('admin.navigation')) {
            return redirect()->to('/admin/navigation')->with('error', 'Permission denied.');
        }
        if (! $this->validate(['label' => 'required', 'url' => 'required'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new NavigationModel())->insert([
            'label'     => $this->request->getPost('label'),
            'url'       => $this->request->getPost('url'),
            'target'    => $this->request->getPost('target') ?? '_self',
            'nav_group' => $this->request->getPost('nav_group') ?? 'primary',
            'sort_order'=> (int) $this->request->getPost('sort_order'),
            'parent_id' => $this->request->getPost('parent_id') ?: null,
        ]);
        return redirect()->to('/admin/navigation')->with('success', 'Nav item added.');
    }

    public function delete(int $id)
    {
        if (! auth()->user()->can('admin.navigation')) {
            return redirect()->to('/admin/navigation')->with('error', 'Permission denied.');
        }
        (new NavigationModel())->delete($id);
        return redirect()->to('/admin/navigation')->with('success', 'Nav item removed.');
    }

    public function reorder()
    {
        if (! auth()->user()->can('admin.navigation')) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied.']);
        }
        $order = $this->request->getPost('order') ?? [];
        $model = new NavigationModel();
        foreach ($order as $i => $id) {
            $model->update((int) $id, ['sort_order' => $i]);
        }
        return $this->response->setJSON(['success' => true]);
    }
}
