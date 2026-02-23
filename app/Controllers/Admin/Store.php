<?php

namespace App\Controllers\Admin;

use App\Services\MarketplaceService;

class Store extends BaseAdminController
{
    public function index(): string
    {
        $service  = new MarketplaceService();
        $items    = $service->fetchAll();
        $storeUrl = setting('Marketplace.storeUrl') ?? 'https://pubvana.net/store';

        return $this->adminView('store/index', array_merge(
            $this->baseData('Pubvana Store', 'marketplace'),
            compact('items', 'storeUrl')
        ));
    }

    public function install()
    {
        $licenseKey = trim($this->request->getPost('license_key') ?? '');
        $slug       = trim($this->request->getPost('slug') ?? '');
        $itemType   = trim($this->request->getPost('item_type') ?? '');

        if (! $slug || ! $itemType) {
            return redirect()->back()->with('error', 'Item slug and item type are required.');
        }

        $service = new MarketplaceService();

        // For free items the license key may be empty; route to installFree via fetchAll lookup
        if ($licenseKey === '') {
            $items = $service->fetchAll();
            $item  = null;
            foreach ($items as $i) {
                if ($i->slug === $slug) { $item = $i; break; }
            }
            if (! $item || ! $item->is_free || empty($item->download_url)) {
                return redirect()->back()->with('error', 'Item not found or not a free item.');
            }
            $ok = $service->installFree($item->download_url, $itemType, $slug);
        } else {
            $ok = $service->installLicensed($licenseKey, $slug, $itemType);
        }

        if ($ok) {
            return redirect()->back()->with('success', ucfirst($itemType) . ' "' . $slug . '" installed successfully.');
        }

        return redirect()->back()->with('error', 'Installation failed. Please check your license key and try again. See application logs for details.');
    }
}
