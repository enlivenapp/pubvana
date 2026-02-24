<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsPremiumToPosts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('posts', [
            'is_premium' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'is_featured',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('posts', 'is_premium');
    }
}
