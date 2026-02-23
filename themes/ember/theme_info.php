<?php

return [
    'name'        => 'Ember',
    'version'     => '1.0.0',
    'author'      => 'Pubvana Team',
    'description' => 'Modern, warm, and approachable blog theme. Amber accents, refined typography, and a clean reading experience.',
    'screenshot'  => 'screenshot.png',

    'widget_areas' => [
        'sidebar'        => 'Main Sidebar',
        'footer-1'       => 'Footer Column 1',
        'footer-2'       => 'Footer Column 2',
        'footer-3'       => 'Footer Column 3',
        'before-content' => 'Before Content',
    ],

    'options' => [
        'show_sidebar' => [
            'type'    => 'checkbox',
            'label'   => 'Show Sidebar',
            'default' => '1',
        ],
        'hero_tagline' => [
            'type'    => 'text',
            'label'   => 'Hero Tagline',
            'default' => '',
        ],
        'accent_color' => [
            'type'    => 'text',
            'label'   => 'Accent Colour (hex)',
            'default' => '#f59e0b',
        ],
        'footer_copyright' => [
            'type'    => 'text',
            'label'   => 'Footer Copyright Text',
            'default' => '',
        ],
    ],
];
