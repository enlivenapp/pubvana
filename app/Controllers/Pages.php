<?php

namespace App\Controllers;

use App\Models\PageModel;
use App\Services\SeoService;

class Pages extends BaseController
{
    public function show(string $slug): string
    {
        $pageModel = new PageModel();
        $page      = $pageModel->findBySlug($slug);

        if (! $page || $page->status !== 'published') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $seo = (new SeoService())->getMeta($page);

        return $this->themeService->view('page', array_merge($this->data, [
            'page' => $page,
            'seo'  => $seo,
        ]));
    }
}
