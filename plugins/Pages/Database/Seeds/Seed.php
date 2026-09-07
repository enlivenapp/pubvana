<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'pages.manage', 'description' => 'Create, edit, and delete pages'],
            ],
        ],
        [
            'table' => 'pages',
            'rows'  => [
                [
                    'title'             => 'Welcome to Pubvana CMS',
                    'slug'              => 'welcome-to-pubvana-cms',
                    'content'           => '<h2>
                                                Welcome to Pubvana CMS v3
                                            </h2>
                                            <h4>
                                                Your Pubvana site is installed and running!
                                            </h4> 
                                            <p>
                                                A modern yet Shared Host friendly Content Management System for personal blogs, small to medium business websites,
                                                company intranets and more. We have completely rebuilt version 3 with a new, fresh administration panel, plugin 
                                                extensions, faster internals, and more included "free" features than you\'d expect in a free CMS.
                                            </p>
                                            <p>
                                                This is your first page. You can delete from the admin panel under <b>Content</b> --> <b>Pages</b>. 
                                                From there you can create new pages, create and organize them with parent pages, and build out your site structure. 
                                                Take a look around the admin dashboard to see all the great things Pubvana v3 has to offer.
                                            </p> 
                                            <p>
                                                Happy publishing!<br>
                                                <a href="https://pubvana-cms.com" target="_blank">Pubvana CMS</a>
                                                </p>',
                    'status'            => 'published',
                    'allow_comments'    => 0,
                    'ai_generated'      => 0,
                    'created_by'        => 1,
                ],
                [
                    'title'             => 'Not WordPress',
                    'slug'              => 'not-wordpress',
                    'content'           => '<h1>This site is not WordPress.</h1> <p>You were redirected here from a WordPress-specific path (login, admin, plugin, or configuration file) that does not exist on this server. If you are a human looking for something, please use the site navigation or home page.</p>',
                    'status'            => 'published',
                    'allow_comments'    => 0,
                    'ai_generated'      => 0,
                    'created_by'        => 1,
                ],
            ],
        ],
    ],
];
