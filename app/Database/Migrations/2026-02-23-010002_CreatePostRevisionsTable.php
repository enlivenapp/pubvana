<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostRevisionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'post_id'          => ['type' => 'INT', 'unsigned' => true],
            'author_id'        => ['type' => 'INT', 'unsigned' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'          => ['type' => 'LONGTEXT', 'null' => true],
            'content_type'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'html'],
            'excerpt'          => ['type' => 'TEXT', 'null' => true],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('post_id');
        $this->forge->createTable('post_revisions');
    }

    public function down(): void
    {
        $this->forge->dropTable('post_revisions', true);
    }
}
