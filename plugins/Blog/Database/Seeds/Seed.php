<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'posts.create', 'description' => 'Create blog posts'],
                ['alias' => 'posts.edit.own', 'description' => 'Edit own blog posts'],
                ['alias' => 'posts.edit.any', 'description' => 'Edit any blog post'],
                ['alias' => 'posts.delete', 'description' => 'Delete blog posts'],
                ['alias' => 'categories.manage', 'description' => 'Manage blog categories'],
                ['alias' => 'tags.manage', 'description' => 'Manage blog tags'],
            ],
        ],
        [
            'table' => 'posts',
            'rows'  => [
                [
                    'title'          => 'Welcome to Pubvana CMS',
                    'slug'           => 'welcome-to-pubvana-cms',
                    'content'        => '<h2>
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
                                            This is your first post. You can delete it from the admin panel under <b>Content</b> --> <b>Posts</b>.
                                            From there you can create new posts, manage categories and tags, upload media, and more.
                                            Take a look around the admin dashboard to see all the great things Pubvana v3 has to offer.
                                        </p>
                                        <p>
                                            Happy publishing!<br>
                                            <a href="https://pubvana-cms.com" target="_blank">Pubvana CMS</a>
                                        </p>',
                    'excerpt'        => 'Your new Pubvana CMS site is up and running. Edit or delete this post from the admin panel to get started.',
                    'status'         => 'published',
                    'author_id'      => 1,
                    'allow_comments' => 1,
                    'ai_generated'   => 0,
                    'published_at'   => date('Y-m-d H:i:s'),
                    'preview_token'  => null,
                ],
            ],
        ],
    ],
];
