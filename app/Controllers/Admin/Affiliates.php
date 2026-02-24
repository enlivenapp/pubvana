<?php

namespace App\Controllers\Admin;

use App\Models\AffiliateClickModel;
use App\Models\AffiliateLinkModel;
use App\Services\ActivityLogger;

class Affiliates extends BaseAdminController
{
    protected AffiliateLinkModel $linkModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->linkModel = new AffiliateLinkModel();
    }

    public function index(): string
    {
        $this->requirePremium();

        return $this->adminView('affiliates/index', array_merge(
            $this->baseData('Affiliate Links', 'affiliates'),
            ['links' => $this->linkModel->withClickCounts()]
        ));
    }

    public function create(): string
    {
        $this->requirePremium();

        return $this->adminView('affiliates/form', array_merge(
            $this->baseData('New Affiliate Link', 'affiliates'),
            ['link' => null]
        ));
    }

    public function store()
    {
        $this->requirePremium();

        if (! $this->validate([
            'name'            => 'required|max_length[150]',
            'slug'            => 'required|max_length[100]|alpha_dash|is_unique[affiliate_links.slug]',
            'destination_url' => 'required|valid_url_strict',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->linkModel->insert([
            'name'            => $this->request->getPost('name'),
            'slug'            => strtolower(trim($this->request->getPost('slug'))),
            'destination_url' => $this->request->getPost('destination_url'),
            'is_active'       => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        ActivityLogger::log('affiliate.created', 'setting', $id, 'Created affiliate link: ' . $this->request->getPost('slug'));

        return redirect()->to('/admin/affiliates')->with('success', 'Affiliate link created.');
    }

    public function edit(int $id): string
    {
        $this->requirePremium();

        $link = $this->linkModel->find($id);
        if (! $link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->adminView('affiliates/form', array_merge(
            $this->baseData('Edit Affiliate Link', 'affiliates'),
            ['link' => $link]
        ));
    }

    public function update(int $id)
    {
        $this->requirePremium();

        $link = $this->linkModel->find($id);
        if (! $link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->validate([
            'name'            => 'required|max_length[150]',
            'slug'            => "required|max_length[100]|alpha_dash|is_unique[affiliate_links.slug,id,{$id}]",
            'destination_url' => 'required|valid_url_strict',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->linkModel->update($id, [
            'name'            => $this->request->getPost('name'),
            'slug'            => strtolower(trim($this->request->getPost('slug'))),
            'destination_url' => $this->request->getPost('destination_url'),
            'is_active'       => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        ActivityLogger::log('affiliate.updated', 'setting', $id, 'Updated affiliate link: ' . $this->request->getPost('slug'));

        return redirect()->to('/admin/affiliates')->with('success', 'Affiliate link updated.');
    }

    public function delete(int $id)
    {
        $this->requirePremium();

        $link = $this->linkModel->find($id);
        if (! $link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Delete clicks first (no FK cascade in migration)
        db_connect()->table('affiliate_clicks')->where('link_id', $id)->delete();
        $this->linkModel->delete($id);

        ActivityLogger::log('affiliate.deleted', 'setting', $id, 'Deleted affiliate link: ' . $link->slug);

        return redirect()->to('/admin/affiliates')->with('success', 'Affiliate link deleted.');
    }

    public function clicks(int $id): string
    {
        $this->requirePremium();

        $link = $this->linkModel->find($id);
        if (! $link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $clickModel = new AffiliateClickModel();
        $clicks     = $clickModel->getForLink($id, 25);

        return $this->adminView('affiliates/clicks', array_merge(
            $this->baseData('Clicks — ' . $link->name, 'affiliates'),
            [
                'link'   => $link,
                'clicks' => $clicks,
                'pager'  => $clickModel->pager,
                'total'  => $clickModel->countForLink($id),
            ]
        ));
    }
}
