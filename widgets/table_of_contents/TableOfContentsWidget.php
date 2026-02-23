<?php

use App\Libraries\BaseWidget;

class TableOfContentsWidget extends BaseWidget
{
    protected string $folder = 'table_of_contents';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        return $this->view('widget', $options);
    }
}
