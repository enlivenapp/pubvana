<?php

namespace App\Models;

use CodeIgniter\Model;

class MarketplaceItemModel extends Model
{
    protected $table      = 'marketplace_items';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'item_type', 'name', 'slug', 'description', 'version', 'price',
        'is_free', 'download_url', 'store_url', 'screenshot_url', 'author', 'installed_version',
    ];
}
