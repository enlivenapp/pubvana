<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * Default group for new registrations.
     */
    public string $defaultGroup = 'subscriber';

    /**
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Day-to-day site administrators.',
        ],
        'editor' => [
            'title'       => 'Editor',
            'description' => 'Can manage all posts, pages, and comments.',
        ],
        'author' => [
            'title'       => 'Author',
            'description' => 'Can create and publish their own posts.',
        ],
        'subscriber' => [
            'title'       => 'Subscriber',
            'description' => 'Registered user with no admin access.',
        ],
    ];

    /**
     * @var array<string, string>
     */
    public array $permissions = [
        'admin.access'      => 'Can access the admin panel',
        'admin.settings'    => 'Can manage site settings',
        'admin.themes'      => 'Can manage themes',
        'admin.widgets'     => 'Can manage widgets',
        'admin.marketplace' => 'Can access the marketplace',
        'admin.navigation'  => 'Can manage navigation menus',
        'posts.create'       => 'Can create posts',
        'posts.edit.own'     => 'Can edit own posts',
        'posts.edit.any'     => 'Can edit any post',
        'posts.delete'       => 'Can delete posts',
        'posts.publish'      => 'Can publish posts',
        'posts.read.premium' => 'Can read premium-gated posts',
        'pages.manage'      => 'Can manage static pages',
        'comments.moderate' => 'Can moderate comments',
        'media.upload'      => 'Can upload media',
        'users.manage'      => 'Can manage users',
    ];

    /**
     * @var array<string, list<string>>
     */
    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'posts.*',
            'pages.*',
            'comments.*',
            'media.*',
            'users.*',
        ],
        'admin' => [
            'admin.access',
            'posts.create',
            'posts.edit.own',
            'posts.edit.any',
            'posts.delete',
            'posts.publish',
            'posts.read.premium',
            'pages.manage',
            'comments.moderate',
            'media.upload',
            'users.manage',
        ],
        'editor' => [
            'admin.access',
            'posts.create',
            'posts.edit.own',
            'posts.edit.any',
            'posts.delete',
            'posts.publish',
            'posts.read.premium',
            'pages.manage',
            'comments.moderate',
            'media.upload',
        ],
        'author' => [
            'admin.access',
            'posts.create',
            'posts.edit.own',
            'posts.publish',
            'posts.read.premium',
            'media.upload',
        ],
        'subscriber' => [
            'posts.read.premium',
        ],
    ];
}
