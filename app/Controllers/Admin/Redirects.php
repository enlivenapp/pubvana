<?php

namespace App\Controllers\Admin;

use App\Models\RedirectModel;

class Redirects extends BaseAdminController
{
    public function index(): string
    {
        $model     = new RedirectModel();
        $redirects = $model->orderBy('created_at', 'DESC')->paginate(20);
        return $this->adminView('redirects/index', array_merge($this->baseData('Redirects', 'redirects'), [
            'redirects' => $redirects,
            'pager'     => $model->pager,
        ]));
    }

    public function store()
    {
        if (! $this->validate(['from_url' => 'required', 'to_url' => 'required'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new RedirectModel())->insert([
            'from_url' => $this->request->getPost('from_url'),
            'to_url'   => $this->request->getPost('to_url'),
            'type'     => $this->request->getPost('type') ?? '301',
        ]);
        return redirect()->to('/admin/redirects')->with('success', 'Redirect added.');
    }

    public function delete(int $id)
    {
        (new RedirectModel())->delete($id);
        return redirect()->to('/admin/redirects')->with('success', 'Redirect deleted.');
    }
}
