<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLogsTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'default'    => '',
            ],
            'subject_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'default'    => '',
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey('subject_type');
        $this->forge->addKey('user_id');
        $this->forge->createTable('activity_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('activity_logs', true);
    }
}
