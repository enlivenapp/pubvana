<?php

use App\Libraries\BaseWidget;

class RecentCommentsWidget extends BaseWidget
{
    protected string $folder = 'recent_comments';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $count    = (int) ($options['count'] ?? 5);
        $comments = db_connect()
            ->table('comments c')
            ->select('c.author_name, c.content, c.created_at, p.slug as post_slug, p.title as post_title')
            ->join('posts p', 'p.id = c.post_id')
            ->where('c.status', 'approved')
            ->orderBy('c.created_at', 'DESC')
            ->limit($count)
            ->get()->getResultObject();

        return $this->view('widget', array_merge($options, ['comments' => $comments]));
    }
}
