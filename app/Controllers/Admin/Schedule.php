<?php

namespace App\Controllers\Admin;

use App\Models\PostModel;

class Schedule extends BaseAdminController
{
    public function view(): string
    {
        $this->requirePremium();

        return $this->adminView('schedule/index', $this->baseData('Post Schedule', 'schedule'));
    }

    public function index()
    {
        $this->requirePremium();

        $model = new PostModel();
        $posts = $model->where('status', 'scheduled')
                       ->where('deleted_at IS NULL')
                       ->orderBy('published_at', 'ASC')
                       ->findAll();

        $events = [];
        foreach ($posts as $post) {
            $events[] = [
                'id'    => $post->id,
                'title' => $post->title,
                'start' => $post->published_at,
                'url'   => base_url('admin/posts/' . $post->id . '/edit'),
                'color' => '#4e73df',
            ];
        }

        return $this->response->setJSON($events);
    }
}
