<?php

namespace App\Controllers\Admin;

use App\Models\CategoryModel;
use App\Models\CommentModel;
use App\Models\PageModel;
use App\Models\PostModel;

class Dashboard extends BaseAdminController
{
    public function index(): string
    {
        $postModel    = new PostModel();
        $pageModel    = new PageModel();
        $commentModel = new CommentModel();
        $db           = db_connect();

        $stats = [
            'posts'           => $postModel->countAllResults(false),
            'published_posts' => $postModel->where('status', 'published')->countAllResults(false),
            'pages'           => $pageModel->countAllResults(false),
            'comments'        => $commentModel->countAllResults(false),
            'pending_comments'=> $commentModel->where('status', 'pending')->countAllResults(false),
            'users'           => $db->table('users')->countAllResults(),
        ];

        $recentPosts = $postModel->select('id, title, slug, status, published_at, created_at')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        $pendingComments = $commentModel->where('status', 'pending')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->findAll();

        return $this->adminView('dashboard/index', array_merge($this->baseData('Dashboard', 'dashboard'), [
            'stats'           => $stats,
            'recent_posts'    => $recentPosts,
            'pending_comments'=> $pendingComments,
        ]));
    }
}
