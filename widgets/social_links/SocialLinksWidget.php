<?php

use App\Libraries\BaseWidget;
use App\Models\SocialModel;

class SocialLinksWidget extends BaseWidget
{
    protected string $folder = 'social_links';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $links = (new SocialModel())->where('is_active', 1)->orderBy('sort_order')->findAll();
        return $this->view('widget', array_merge($options, ['links' => $links]));
    }
}
