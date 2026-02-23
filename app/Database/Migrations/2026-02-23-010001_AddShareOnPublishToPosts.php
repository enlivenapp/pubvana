<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShareOnPublishToPosts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('posts', [
            'share_on_publish' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'is_featured',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('posts', 'share_on_publish');
    }
}
