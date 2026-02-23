<?php

namespace App\Controllers;

use App\Models\PostModel;
use App\Services\SeoService;

class Search extends BaseController
{
    public function index(): string
    {
        $q       = trim($this->request->getGet('q') ?? '');
        $perPage = (int) (setting('App.postsPerPage') ?? 10);
        $posts   = [];
        $pager   = null;

        if ($q !== '') {
            $postModel = new PostModel();
            $posts     = $postModel->published()
                ->groupStart()
                    ->like('title', $q)
                    ->orLike('content', $q)
                    ->orLike('excerpt', $q)
                ->groupEnd()
                ->orderBy('published_at', 'DESC')
                ->paginate($perPage);
            $pager = $postModel->pager;
        }

        $fake = (object) ['title' => 'Search: ' . $q, 'meta_title' => null, 'meta_description' => null];
        return $this->themeService->view('search', array_merge($this->data, [
            'query'  => $q,
            'posts'  => $posts,
            'pager'  => $pager,
            'seo'    => (new SeoService())->getMeta($fake),
        ]));
    }
}
