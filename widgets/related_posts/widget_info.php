<?php
return [
    'name'        => 'Related Posts',
    'description' => 'Display posts related to the current post by category and tags',
    'version'     => '1.0.0',
    'options'     => [
        'title'          => ['type' => 'text',     'label' => 'Title',          'default' => 'Related Posts'],
        'count'          => ['type' => 'number',   'label' => 'Number of Posts', 'default' => '4'],
        'show_thumbnail' => ['type' => 'checkbox', 'label' => 'Show Thumbnail', 'default' => '1'],
    ],
];
