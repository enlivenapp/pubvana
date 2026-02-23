<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTagsToPostsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'tag_id'  => ['type' => 'INT', 'unsigned' => true],
            'post_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey(['tag_id', 'post_id'], true);
        $this->forge->createTable('tags_to_posts');
    }

    public function down()
    {
        $this->forge->dropTable('tags_to_posts', true);
    }
}
