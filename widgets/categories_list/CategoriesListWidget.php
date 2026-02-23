<?php

use App\Libraries\BaseWidget;
use App\Models\CategoryModel;

class CategoriesListWidget extends BaseWidget
{
    protected string $folder = 'categories_list';

    public function getInfo(): array
    {
        return require __DIR__ . '/widget_info.php';
    }

    protected function buildOutput(array $options): string
    {
        $categories = (new CategoryModel())->getWithPostCount();
        return $this->view('widget', array_merge($options, ['categories' => $categories]));
    }
}
