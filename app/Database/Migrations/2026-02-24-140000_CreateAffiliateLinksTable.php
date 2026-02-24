<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAffiliateLinksTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => '',
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
            'destination_url' => [
                'type'       => 'TEXT',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('affiliate_links', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('affiliate_links', true);
    }
}
