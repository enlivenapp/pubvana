<?php
return [
    'name'    => 'Recent Comments',
    'description' => 'Display recent comments',
    'version' => '1.0.0',
    'options' => [
        'title' => ['type' => 'text',   'label' => 'Title', 'default' => 'Recent Comments'],
        'count' => ['type' => 'number', 'label' => 'Number of Comments', 'default' => '5'],
    ],
];
