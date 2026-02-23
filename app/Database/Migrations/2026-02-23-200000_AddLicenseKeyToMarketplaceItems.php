<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLicenseKeyToMarketplaceItems extends Migration
{
    public function up()
    {
        $this->forge->addColumn('marketplace_items', [
            'license_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'installed_version',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('marketplace_items', 'license_key');
    }
}
