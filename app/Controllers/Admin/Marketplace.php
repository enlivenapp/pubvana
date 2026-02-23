<?php

namespace App\Controllers\Admin;

use App\Services\MarketplaceService;

class Marketplace extends BaseAdminController
{
    protected MarketplaceService $service;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->service = new MarketplaceService();
    }

    public function index(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchAll(),
            'filter'  => '',
            'updates' => $this->service->checkUpdates(),
        ]));
    }

    public function themes(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Themes — Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchThemes(),
            'filter'  => 'theme',
            'updates' => [],
        ]));
    }

    public function widgets(): string
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        return $this->adminView('marketplace/index', array_merge($this->baseData('Widgets — Marketplace', 'marketplace'), [
            'items'   => $this->service->fetchWidgets(),
            'filter'  => 'widget',
            'updates' => [],
        ]));
    }

    public function refresh()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $this->service->refreshCache();
        return redirect()->to('/admin/marketplace')->with('success', 'Marketplace cache refreshed.');
    }

    public function install()
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin/marketplace')->with('error', 'Permission denied.');
        }
        $url    = $this->request->getPost('download_url');
        $type   = $this->request->getPost('item_type') ?? $this->request->getPost('type');
        $folder = $this->request->getPost('slug') ?? $this->request->getPost('folder');

        if (! $url || ! in_array($type, ['theme', 'widget'], true) || ! $folder) {
            return redirect()->to('/admin/marketplace')->with('error', 'Invalid install request.');
        }

        $ok = $this->service->installFree($url, $type, $folder);
        if ($ok) {
            return redirect()->to('/admin/marketplace')->with('success', ucfirst($type) . ' installed successfully.');
        }
        return redirect()->to('/admin/marketplace')->with('error', 'Installation failed. Check logs.');
    }

    public function update(string $slug)
    {
        if (! auth()->user()->can('admin.marketplace')) {
            return redirect()->to('/admin')->with('error', 'Permission denied.');
        }
        $item = db_connect()->table('marketplace_items')->where('slug', $slug)->get()->getRowObject();
        if (! $item || ! $item->download_url) {
            return redirect()->to('/admin/marketplace')->with('error', 'Cannot update this item.');
        }
        $ok = $this->service->installFree($item->download_url, $item->item_type, $slug);
        if ($ok) {
            return redirect()->to('/admin/marketplace')->with('success', 'Updated successfully.');
        }
        return redirect()->to('/admin/marketplace')->with('error', 'Update failed.');
    }
}
