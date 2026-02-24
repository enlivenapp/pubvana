<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBrokenLinkResultsTable extends Migration
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
            'source_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,  // 'post' | 'page'
                'default'    => 'post',
            ],
            'source_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'source_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'url' => [
                'type' => 'TEXT',
            ],
            'url_hash' => [
                // SHA1 of url — used for unique constraint since TEXT can't be indexed directly
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => '',
            ],
            'http_status' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
            ],
            'error_message' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'dismissed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'last_checked_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey(['source_type', 'source_id', 'url_hash']);
        $this->forge->addKey('http_status');
        $this->forge->addKey('dismissed');
        $this->forge->createTable('broken_link_results', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('broken_link_results', true);
    }
}
