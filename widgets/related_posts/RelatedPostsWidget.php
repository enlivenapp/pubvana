<?php

use App\Libraries\BaseWidget;

class RelatedPostsWidget extends BaseWidget
{
    protected string $folder = 'related_posts';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $request  = service('request');
        $segment1 = $request->getUri()->getSegment(1);
        $segment2 = $request->getUri()->getSegment(2);

        $posts = [];
        if ($segment1 === 'blog' && $segment2) {
            $db   = db_connect();
            $post = $db->table('posts')
                       ->where('slug', $segment2)
                       ->where('status', 'published')
                       ->get()->getRowObject();

            if ($post) {
                $catIds = array_column(
                    $db->table('posts_to_categories')
                       ->where('post_id', $post->id)
                       ->get()->getResultArray(),
                    'category_id'
                );
                $tagIds = array_column(
                    $db->table('tags_to_posts')
                       ->where('post_id', $post->id)
                       ->get()->getResultArray(),
                    'tag_id'
                );
                $count = (int) ($options['count'] ?? 4);
                $posts = $this->findRelated($post->id, $catIds, $tagIds, $count);
            }
        }

        return $this->view('widget', array_merge($options, ['posts' => $posts]));
    }

    private function findRelated(int $excludeId, array $catIds, array $tagIds, int $count): array
    {
        $db     = db_connect();
        $scores = [];

        // +2 per shared category
        if ($catIds) {
            $rows = $db->table('posts_to_categories ptc')
                       ->select('ptc.post_id, p.title, p.slug, p.featured_image, p.published_at')
                       ->join('posts p', 'p.id = ptc.post_id')
                       ->whereIn('ptc.category_id', $catIds)
                       ->where('ptc.post_id !=', $excludeId)
                       ->where('p.status', 'published')
                       ->get()->getResultArray();
            foreach ($rows as $row) {
                $id = $row['post_id'];
                if (!isset($scores[$id])) {
                    $scores[$id] = array_merge($row, ['score' => 0]);
                }
                $scores[$id]['score'] += 2;
            }
        }

        // +1 per shared tag
        if ($tagIds) {
            $rows = $db->table('tags_to_posts ttp')
                       ->select('ttp.post_id, p.title, p.slug, p.featured_image, p.published_at')
                       ->join('posts p', 'p.id = ttp.post_id')
                       ->whereIn('ttp.tag_id', $tagIds)
                       ->where('ttp.post_id !=', $excludeId)
                       ->where('p.status', 'published')
                       ->get()->getResultArray();
            foreach ($rows as $row) {
                $id = $row['post_id'];
                if (!isset($scores[$id])) {
                    $scores[$id] = array_merge($row, ['score' => 0]);
                }
                $scores[$id]['score'] += 1;
            }
        }

        if (!$scores) {
            return [];
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_values($scores), 0, $count);
    }
}
