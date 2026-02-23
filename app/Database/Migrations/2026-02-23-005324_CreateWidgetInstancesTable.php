<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWidgetInstancesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'widget_id'      => ['type' => 'INT', 'unsigned' => true],
            'widget_area_id' => ['type' => 'INT', 'unsigned' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'options_json'   => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('widget_area_id');
        $this->forge->createTable('widget_instances', true);
    }

    public function down()
    {
        $this->forge->dropTable('widget_instances', true);
    }
}
