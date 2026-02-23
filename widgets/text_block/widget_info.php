<?php
return [
    'name'    => 'Text Block',
    'description' => 'Display arbitrary HTML/text',
    'version' => '1.0.0',
    'options' => [
        'title'   => ['type' => 'text',     'label' => 'Title',   'default' => ''],
        'content' => ['type' => 'textarea', 'label' => 'Content', 'default' => ''],
    ],
];
