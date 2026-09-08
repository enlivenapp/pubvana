<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'navigation.edit', 'description' => 'Manage navigation menus'],
                ['alias' => 'plugins.manage', 'description' => 'View and manage installed plugins'],
            ],
        ],
        [
            'table' => 'navigation',
            'rows'  => [
                [
                    'label'      => 'Home',
                    'url'        => '/',
                    'sort_order' => 0,
                    'nav_group'  => 'primary',
                    'target'     => '_self',
                ],
                [
                    'label'      => 'Blog',
                    'url'        => '/blog',
                    'sort_order' => 1,
                    'nav_group'  => 'primary',
                    'target'     => '_self',
                ],
            ],
        ],
        [
            'table' => 'settings',
            'rows'  => [
                ['key' => 'CMS.siteName',       'value' => 'Pubvana v3',                          'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.siteUrl',        'value' => 'http://localhost',                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.adminEmail',     'value' => 'admin@example.com',                   'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.defaultTimezone','value' => 'UTC',                                 'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.siteByline',     'value' => '',                                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.logo',           'value' => '',                                    'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.favicon',        'value' => '/favicon.ico',                         'type' => 'string', 'autoload' => true],
                ['key' => 'CMS.copyright',      'value' => '© Pubvana v3',                        'type' => 'string', 'autoload' => true],
            ],
        ],
        [
            'table' => 'plugin_state',
            'rows'  => [
                // Shipped-active bundled core plugins. Anything not listed here
                // (or explicitly configured) is discovered disabled: its code
                // runs nothing until an admin enables it on the Plugins page.
                // AiAssistant (pubvana/ai) is deliberately absent, so it ships off.
                ['plugin_id' => 'pubvana/activity-log',  'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/ai',            'enabled' => 0, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/analytics',     'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/backups',       'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/blog',          'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/brokenlinks',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/comments',      'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/core-blocks',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/forms',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/marketplace',   'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/media',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/pages',         'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/profiles',      'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/redirects',     'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/search',        'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/seo',           'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/sitehealth',    'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/social-links',  'enabled' => 1, 'priority' => 50, 'required' => 0],
                ['plugin_id' => 'pubvana/updates',       'enabled' => 1, 'priority' => 50, 'required' => 0],
            ],
        ],
    ],
];
