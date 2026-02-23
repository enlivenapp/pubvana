<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'          => ['type' => 'LONGTEXT', 'null' => true],
            'content_type'     => ['type' => 'ENUM', 'constraint' => ['html', 'markdown'], 'default' => 'html'],
            'excerpt'          => ['type' => 'TEXT', 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'scheduled'], 'default' => 'draft'],
            'featured_image'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author_id'        => ['type' => 'INT', 'unsigned' => true],
            'published_at'     => ['type' => 'DATETIME', 'null' => true],
            'views'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'is_featured'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'TEXT', 'null' => true],
            'lang'             => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('author_id');
        $this->forge->addKey('status');
        $this->forge->createTable('posts');
    }

    public function down()
    {
        $this->forge->dropTable('posts', true);
    }
}
