<?php
return [
    'name'    => 'Tag Cloud',
    'description' => 'Display a tag cloud',
    'version' => '1.0.0',
    'options' => [
        'title'    => ['type' => 'text',   'label' => 'Title',    'default' => 'Tags'],
        'max_tags' => ['type' => 'number', 'label' => 'Max Tags', 'default' => '30'],
    ],
];
