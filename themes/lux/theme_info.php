<?php
return [
    'name'        => 'Lux',
    'version'     => '1.0.0',
    'author'      => 'Bootswatch / Pubvana',
    'description' => 'Elegant, sophisticated light theme with refined typography.',
    'screenshot'  => 'screenshot.png',
    'parent'      => 'default',
    'widget_areas' => [
        'sidebar'        => 'Main Sidebar',
        'footer-1'       => 'Footer Column 1',
        'footer-2'       => 'Footer Column 2',
        'footer-3'       => 'Footer Column 3',
        'before-content' => 'Before Content',
    ],
    'options' => [
        'show_sidebar'     => ['type' => 'checkbox', 'label' => 'Show Sidebar',          'default' => '1'],
        'footer_copyright' => ['type' => 'text',     'label' => 'Footer Copyright Text', 'default' => ''],
    ],
];
