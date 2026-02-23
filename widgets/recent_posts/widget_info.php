<?php
return [
    'name'        => 'Recent Posts',
    'description' => 'Display recent posts',
    'version'     => '1.0.0',
    'options'     => [
        'title'        => ['type' => 'text',     'label' => 'Title',        'default' => 'Recent Posts'],
        'count'        => ['type' => 'number',   'label' => 'Number of Posts', 'default' => '5'],
        'show_date'    => ['type' => 'checkbox', 'label' => 'Show Date',    'default' => '1'],
        'show_excerpt' => ['type' => 'checkbox', 'label' => 'Show Excerpt', 'default' => '0'],
    ],
];
