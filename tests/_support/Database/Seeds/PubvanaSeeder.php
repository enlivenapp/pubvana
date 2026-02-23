<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Shared seeder for all Pubvana DB/Feature/Integration tests.
 *
 * Inserts the minimum rows required by the test suite without relying on
 * Shield's UserModel event hooks (which assume a full HTTP request context).
 */
class PubvanaSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ----------------------------------------------------------------
        // Clear all tables (in FK-safe order) before inserting.
        // DELETE FROM is dramatically faster than TRUNCATE on InnoDB for
        // small tables because it avoids DDL lock overhead (~1-2s per
        // TRUNCATE vs near-instant DELETE).
        // ----------------------------------------------------------------
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'tags_to_posts', 'posts_to_categories', 'comments', 'posts',
            'pages', 'tags', 'categories', 'author_profiles', 'themes',
            'auth_groups_users', 'auth_identities', 'users',
        ] as $table) {
            $this->db->query('DELETE FROM ' . $this->db->prefixTable($table));
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');

        // ----------------------------------------------------------------
        // Users
        // ----------------------------------------------------------------
        $this->db->table('users')->insert([
            'id'         => 1,
            'username'   => 'testadmin',
            'active'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Auth identities (email_password)
        // ----------------------------------------------------------------
        $this->db->table('auth_identities')->insert([
            'user_id'    => 1,
            'type'       => 'email_password',
            'name'       => 'admin@test.com',
            'secret'     => 'admin@test.com',
            'secret2'    => password_hash('Admin@12345', PASSWORD_DEFAULT),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Auth groups
        // ----------------------------------------------------------------
        $this->db->table('auth_groups_users')->insert([
            'user_id'    => 1,
            'group'      => 'superadmin',
            'created_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Themes
        // ----------------------------------------------------------------
        $this->db->table('themes')->insert([
            'id'         => 1,
            'name'       => 'Default',
            'folder'     => 'default',
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Categories
        // ----------------------------------------------------------------
        $this->db->table('categories')->insert([
            'id'         => 1,
            'name'       => 'News',
            'slug'       => 'news',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('categories')->insert([
            'id'         => 2,
            'name'       => 'Tutorials',
            'slug'       => 'tutorials',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Tags
        // ----------------------------------------------------------------
        $this->db->table('tags')->insert([
            'id'   => 1,
            'name' => 'PHP',
            'slug' => 'php',
        ]);
        $this->db->table('tags')->insert([
            'id'   => 2,
            'name' => 'CodeIgniter',
            'slug' => 'codeigniter',
        ]);

        // ----------------------------------------------------------------
        // Posts
        // ----------------------------------------------------------------
        // Post 1: published, no category/tag
        $this->db->table('posts')->insert([
            'id'           => 1,
            'title'        => 'Hello World',
            'slug'         => 'hello-world',
            'content'      => '<p>Hello World content.</p>',
            'content_type' => 'html',
            'status'       => 'published',
            'author_id'    => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'views'        => 0,
            'is_featured'  => 0,
            'lang'         => 'en',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Post 2: draft
        $this->db->table('posts')->insert([
            'id'           => 2,
            'title'        => 'A Draft Post',
            'slug'         => 'a-draft-post',
            'content'      => '<p>Draft content.</p>',
            'content_type' => 'html',
            'status'       => 'draft',
            'author_id'    => 1,
            'published_at' => null,
            'views'        => 0,
            'is_featured'  => 0,
            'lang'         => 'en',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Post 3: published, category 1
        $this->db->table('posts')->insert([
            'id'           => 3,
            'title'        => 'Cat Post',
            'slug'         => 'cat-post',
            'content'      => '<p>Category post content.</p>',
            'content_type' => 'html',
            'status'       => 'published',
            'author_id'    => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'views'        => 5,
            'is_featured'  => 1,
            'lang'         => 'en',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Post 4: published, tag 1
        $this->db->table('posts')->insert([
            'id'           => 4,
            'title'        => 'Tag Post',
            'slug'         => 'tag-post',
            'content'      => '<p>Tag post content.</p>',
            'content_type' => 'html',
            'status'       => 'published',
            'author_id'    => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'views'        => 10,
            'is_featured'  => 0,
            'lang'         => 'en',
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // ----------------------------------------------------------------
        // Post ↔ Category relations
        // ----------------------------------------------------------------
        $this->db->table('posts_to_categories')->insert([
            'post_id'     => 3,
            'category_id' => 1,
        ]);

        // ----------------------------------------------------------------
        // Tag ↔ Post relations
        // ----------------------------------------------------------------
        $this->db->table('tags_to_posts')->insert([
            'post_id' => 4,
            'tag_id'  => 1,
        ]);

        // ----------------------------------------------------------------
        // Pages
        // ----------------------------------------------------------------
        $this->db->table('pages')->insert([
            'id'         => 1,
            'title'      => 'About',
            'slug'       => 'about',
            'content'    => '<p>About page content.</p>',
            'status'     => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db->table('pages')->insert([
            'id'         => 2,
            'title'      => 'Secret Page',
            'slug'       => 'secret-page',
            'content'    => '<p>Draft page content.</p>',
            'status'     => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ----------------------------------------------------------------
        // Comments
        // ----------------------------------------------------------------
        // Comment 1: approved, no parent
        $this->db->table('comments')->insert([
            'id'           => 1,
            'post_id'      => 1,
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
            'content'      => 'Great post!',
            'status'       => 'approved',
            'parent_id'    => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        // Comment 2: approved, child of comment 1
        $this->db->table('comments')->insert([
            'id'           => 2,
            'post_id'      => 1,
            'author_name'  => 'Bob',
            'author_email' => 'bob@example.com',
            'content'      => 'Thanks Alice!',
            'status'       => 'approved',
            'parent_id'    => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        // Comment 3: pending, no parent
        $this->db->table('comments')->insert([
            'id'           => 3,
            'post_id'      => 1,
            'author_name'  => 'Spammer',
            'author_email' => 'spam@example.com',
            'content'      => 'Buy cheap pills!',
            'status'       => 'pending',
            'parent_id'    => null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }
}
