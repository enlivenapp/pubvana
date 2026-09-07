<?php

return [
    'install' => [
        [
            'table' => 'settings',
            'rows'  => [
                ['key' => 'Comments.comments_enabled',     'value' => '1',     'type' => 'boolean', 'autoload' => 1],
                ['key' => 'Comments.allow_guest_comments', 'value' => '0',     'type' => 'boolean', 'autoload' => 1],
                ['key' => 'Comments.default_status',       'value' => 'pending', 'type' => 'string', 'autoload' => 1],
                ['key' => 'Comments.max_nesting_depth',    'value' => '3',     'type' => 'integer', 'autoload' => 1],
                ['key' => 'Comments.enabledHosts',         'value' => '[]',    'type' => 'string',  'autoload' => 1],
            ],
        ],

        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'comments.moderate', 'description' => 'Moderate comments (approve, reject, delete)'],
            ],
        ],
    ],
];
