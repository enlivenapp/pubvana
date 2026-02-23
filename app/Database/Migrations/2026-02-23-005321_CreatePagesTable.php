<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'          => ['type' => 'LONGTEXT', 'null' => true],
            'content_type'     => ['type' => 'ENUM', 'constraint' => ['html', 'markdown'], 'default' => 'html'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'draft'],
            'parent_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'meta_title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'meta_description' => ['type' => 'TEXT', 'null' => true],
            'is_system'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('pages');
    }

    public function down()
    {
        $this->forge->dropTable('pages', true);
    }
}
