<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostsToCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'post_id'     => ['type' => 'INT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['post_id', 'category_id'], true);
        $this->forge->createTable('posts_to_categories', true);
    }

    public function down()
    {
        $this->forge->dropTable('posts_to_categories', true);
    }
}
