<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'filename'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'path'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'mime_type'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'size'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'alt_text'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('media');
    }

    public function down()
    {
        $this->forge->dropTable('media', true);
    }
}
