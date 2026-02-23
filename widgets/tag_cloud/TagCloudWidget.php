<?php

use App\Libraries\BaseWidget;
use App\Models\TagModel;

class TagCloudWidget extends BaseWidget
{
    protected string $folder = 'tag_cloud';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $limit = (int) ($options['max_tags'] ?? 30);
        $tags  = (new TagModel())->getWithPostCount();
        $tags  = array_slice($tags, 0, $limit);
        return $this->view('widget', array_merge($options, ['tags' => $tags]));
    }
}
