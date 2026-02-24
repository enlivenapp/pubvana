<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageViewsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['post', 'page'],
                'null'       => false,
            ],
            'entity_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'referrer_domain' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'viewed_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('viewed_at');
        $this->forge->addKey('referrer_domain');

        $this->forge->createTable('page_views', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('page_views', true);
    }
}
