<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWidgetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'folder'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'version'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folder');
        $this->forge->createTable('widgets', true);
    }

    public function down()
    {
        $this->forge->dropTable('widgets', true);
    }
}
