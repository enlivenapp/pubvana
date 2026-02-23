<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'post_id'      => ['type' => 'INT', 'unsigned' => true],
            'author_name'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'author_email' => ['type' => 'VARCHAR', 'constraint' => 255],
            'content'      => ['type' => 'TEXT'],
            'status'       => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'spam', 'trash'], 'default' => 'pending'],
            'parent_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('post_id');
        $this->forge->addKey('status');
        $this->forge->createTable('comments');
    }

    public function down()
    {
        $this->forge->dropTable('comments', true);
    }
}
