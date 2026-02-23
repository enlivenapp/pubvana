<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNavigationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'parent_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'target'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '_self'],
            'nav_group'  => ['type' => 'ENUM', 'constraint' => ['primary', 'footer'], 'default' => 'primary'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nav_group');
        $this->forge->createTable('navigation', true);
    }

    public function down()
    {
        $this->forge->dropTable('navigation', true);
    }
}
