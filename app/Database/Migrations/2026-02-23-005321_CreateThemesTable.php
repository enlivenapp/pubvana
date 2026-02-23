<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThemesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'folder'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'version'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'installed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folder');
        $this->forge->createTable('themes', true);
    }

    public function down()
    {
        $this->forge->dropTable('themes', true);
    }
}
