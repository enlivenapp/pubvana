<?php
return [
    'name'        => 'Table of Contents',
    'description' => 'Auto-generates a table of contents from post headings',
    'version'     => '1.0.0',
    'options'     => [
        'title'        => ['type' => 'text',   'label' => 'Title',        'default' => 'Contents'],
        'min_headings' => ['type' => 'number', 'label' => 'Min Headings', 'default' => '2'],
        'max_depth'    => [
            'type'    => 'select',
            'label'   => 'Max Depth',
            'default' => 'h3',
            'options' => ['h2' => 'H2 only', 'h3' => 'H2 + H3', 'h4' => 'H2 + H3 + H4'],
        ],
    ],
];
