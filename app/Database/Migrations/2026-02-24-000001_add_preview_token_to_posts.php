<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPreviewTokenToPosts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('posts', [
            'preview_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'meta_description',
            ],
        ]);

        $this->db->query('ALTER TABLE posts ADD UNIQUE KEY uq_posts_preview_token (preview_token)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE posts DROP INDEX uq_posts_preview_token');
        $this->forge->dropColumn('posts', 'preview_token');
    }
}
