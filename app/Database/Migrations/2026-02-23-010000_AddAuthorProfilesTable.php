<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthorProfilesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bio'          => ['type' => 'TEXT', 'null' => true],
            'avatar'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'website'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'twitter'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'facebook'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'linkedin'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('author_profiles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('author_profiles', true);
    }
}
