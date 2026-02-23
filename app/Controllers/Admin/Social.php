<?php

namespace App\Controllers\Admin;

use App\Models\SocialModel;

class Social extends BaseAdminController
{
    public function index(): string
    {
        $links = (new SocialModel())->orderBy('sort_order')->findAll();
        return $this->adminView('social/index', array_merge($this->baseData('Social Links', 'social'), ['links' => $links]));
    }

    public function store()
    {
        if (! $this->validate(['platform' => 'required', 'url' => 'required|valid_url_strict'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new SocialModel())->insert([
            'platform'   => $this->request->getPost('platform'),
            'url'        => $this->request->getPost('url'),
            'icon'       => $this->request->getPost('icon') ?? 'fab fa-link',
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => 1,
        ]);
        return redirect()->to('/admin/social')->with('success', 'Social link added.');
    }

    public function delete(int $id)
    {
        (new SocialModel())->delete($id);
        return redirect()->to('/admin/social')->with('success', 'Link deleted.');
    }
}
