<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSocialTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'platform'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('social');
    }

    public function down()
    {
        $this->forge->dropTable('social', true);
    }
}
