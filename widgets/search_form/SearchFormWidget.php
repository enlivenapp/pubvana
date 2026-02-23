<?php

use App\Libraries\BaseWidget;

class SearchFormWidget extends BaseWidget
{
    protected string $folder = 'search_form';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }
}
