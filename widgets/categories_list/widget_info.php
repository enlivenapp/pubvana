<?php
return [
    'name'    => 'Categories List',
    'description' => 'Display post categories',
    'version' => '1.0.0',
    'options' => [
        'title'      => ['type' => 'text',     'label' => 'Title',      'default' => 'Categories'],
        'show_count' => ['type' => 'checkbox', 'label' => 'Show Count', 'default' => '1'],
    ],
];
