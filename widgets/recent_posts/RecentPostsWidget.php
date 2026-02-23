<?php

use App\Libraries\BaseWidget;
use App\Models\PostModel;

class RecentPostsWidget extends BaseWidget
{
    protected string $folder = 'recent_posts';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $count = (int) ($options['count'] ?? 5);
        $posts = (new PostModel())->published()->orderBy('published_at', 'DESC')->limit($count)->findAll();
        return $this->view('widget', array_merge($options, ['posts' => $posts]));
    }
}
