<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarketplaceItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_type'         => ['type' => 'ENUM', 'constraint' => ['theme', 'widget']],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'       => ['type' => 'TEXT', 'null' => true],
            'version'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'price'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => '0.00'],
            'is_free'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'download_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'store_url'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'screenshot_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'installed_version' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('marketplace_items', true);
    }

    public function down()
    {
        $this->forge->dropTable('marketplace_items', true);
    }
}
