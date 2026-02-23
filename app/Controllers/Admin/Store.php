<?php

namespace App\Controllers\Admin;

use App\Services\MarketplaceService;

class Store extends BaseAdminController
{
    public function index(): string
    {
        $paidItems = array_filter(
            (new MarketplaceService())->fetchAll(),
            fn($item) => ! $item->is_free
        );

        return $this->adminView('store/index', array_merge($this->baseData('Pubvana Store', 'marketplace'), [
            'paid_items' => array_values($paidItems),
            'store_url'  => setting('Marketplace.storeUrl') ?? 'https://pubvana.net/store',
        ]));
    }
}
