<?php
return [
    'name'    => 'Social Links',
    'description' => 'Display social media links',
    'version' => '1.0.0',
    'options' => [
        'title' => ['type' => 'text',   'label' => 'Title', 'default' => 'Follow Us'],
        'style' => ['type' => 'select', 'label' => 'Style', 'default' => 'icons', 'options' => ['icons' => 'Icons only', 'icons+text' => 'Icons + Text']],
    ],
];
