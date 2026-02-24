<?php

namespace App\Controllers;

use App\Models\AffiliateClickModel;
use App\Models\AffiliateLinkModel;

class AffiliateRedirect extends BaseController
{
    public function go(string $slug)
    {
        $linkModel = new AffiliateLinkModel();
        $link      = $linkModel->findActiveBySlug($slug);

        if (! $link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Log the click — hash the IP so no raw PII is stored
        $ip       = $this->request->getIPAddress();
        $ipHash   = hash('sha256', $ip . 'pubvana_salt');
        $referrer = $this->request->getServer('HTTP_REFERER');

        (new AffiliateClickModel())->insert([
            'link_id'    => $link->id,
            'ip_hash'    => $ipHash,
            'referrer'   => $referrer ? mb_substr($referrer, 0, 500) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to($link->destination_url, 301);
    }
}
