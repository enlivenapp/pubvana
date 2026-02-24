<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAffiliateClicksTable extends Migration
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
            'link_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ip_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => '',
            ],
            'referrer' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('link_id');
        $this->forge->createTable('affiliate_clicks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('affiliate_clicks', true);
    }
}
