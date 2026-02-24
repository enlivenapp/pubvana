<?php

namespace App\Commands;

use App\Models\PostModel;
use App\Services\ActivityLogger;
use App\Services\SocialSharingService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PublishScheduledPosts extends BaseCommand
{
    protected $group       = 'Pubvana';
    protected $name        = 'posts:publish';
    protected $description = 'Publish any scheduled posts whose publish date has passed.';
    protected $usage       = 'posts:publish';

    public function run(array $params): void
    {
        $db    = db_connect();
        $model = new PostModel();
        $now   = date('Y-m-d H:i:s');

        $due = $db->table('posts')
            ->select('id, title, share_on_publish')
            ->where('status', 'scheduled')
            ->where('published_at <=', $now)
            ->whereNull('deleted_at')
            ->get()->getResultObject();

        if (empty($due)) {
            CLI::write('No scheduled posts are due for publishing.', 'yellow');
            return;
        }

        $count = 0;

        foreach ($due as $post) {
            $model->update($post->id, ['status' => 'published']);

            ActivityLogger::log(
                'post.published',
                'post',
                (int) $post->id,
                'Published scheduled post: ' . $post->title
            );

            // Trigger social share if enabled
            if ($post->share_on_publish) {
                try {
                    $full = $model->find($post->id);
                    (new SocialSharingService())->share($full);
                } catch (\Throwable $e) {
                    CLI::write('  Social share failed for post #' . $post->id . ': ' . $e->getMessage(), 'yellow');
                }
            }

            CLI::write('  Published: [#' . $post->id . '] ' . $post->title, 'green');
            $count++;
        }

        CLI::write('');
        CLI::write($count . ' post' . ($count !== 1 ? 's' : '') . ' published.', 'green');
    }
}
