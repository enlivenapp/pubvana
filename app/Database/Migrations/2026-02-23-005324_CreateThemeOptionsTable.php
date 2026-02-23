<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThemeOptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'theme_id'     => ['type' => 'INT', 'unsigned' => true],
            'option_key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'option_value' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('theme_id');
        $this->forge->createTable('theme_options');
    }

    public function down()
    {
        $this->forge->dropTable('theme_options', true);
    }
}
