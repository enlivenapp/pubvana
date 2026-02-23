<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRedirectsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'from_url'   => ['type' => 'VARCHAR', 'constraint' => 500],
            'to_url'     => ['type' => 'VARCHAR', 'constraint' => 500],
            'type'       => ['type' => 'ENUM', 'constraint' => ['301', '302'], 'default' => '301'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('redirects', true);
    }

    public function down()
    {
        $this->forge->dropTable('redirects', true);
    }
}
