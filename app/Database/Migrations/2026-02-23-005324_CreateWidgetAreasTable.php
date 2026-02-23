<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWidgetAreasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'theme_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['theme_id', 'slug']);
        $this->forge->createTable('widget_areas');
    }

    public function down()
    {
        $this->forge->dropTable('widget_areas', true);
    }
}
