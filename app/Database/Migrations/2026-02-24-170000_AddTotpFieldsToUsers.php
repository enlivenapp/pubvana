<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTotpFieldsToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'totp_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'active',
            ],
            'totp_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
                'after'      => 'totp_secret',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['totp_secret', 'totp_enabled']);
    }
}
