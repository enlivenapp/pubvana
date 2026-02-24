<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseValidationToMarketplaceItems extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('marketplace_items', [
            'license_last_checked' => [
                'type'       => 'DATETIME',
                'null'       => true,
                'default'    => null,
                'after'      => 'license_key',
            ],
            'license_valid' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => true,
                'default'    => null,
                'after'      => 'license_last_checked',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('marketplace_items', ['license_last_checked', 'license_valid']);
    }
}
