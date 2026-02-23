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
        if (! $this->validate([
            'from_url' => 'required|string|max_length[500]',
            'to_url'   => 'required|string|max_length[500]',
            'type'     => 'permit_empty|in_list[301,302]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $toUrl = $this->request->getPost('to_url');

        // Block javascript: and data: URI schemes which could be used for phishing/XSS
        if (preg_match('/^\s*(javascript|data|vbscript):/i', $toUrl)) {
            return redirect()->back()->withInput()->with('error', 'Invalid redirect destination URL.');
        }

        (new RedirectModel())->insert([
            'from_url' => $this->request->getPost('from_url'),
            'to_url'   => $toUrl,
            'type'     => $this->request->getPost('type') ?: '301',
        ]);
        return redirect()->to('/admin/redirects')->with('success', 'Redirect added.');
    }

    public function delete(int $id)
    {
        (new RedirectModel())->delete($id);
        return redirect()->to('/admin/redirects')->with('success', 'Redirect deleted.');
    }
}
