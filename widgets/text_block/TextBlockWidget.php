<?php

use App\Libraries\BaseWidget;

class TextBlockWidget extends BaseWidget
{
    protected string $folder = 'text_block';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }
}
