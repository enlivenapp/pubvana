<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_post_count extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'post_count' => array('type' => 'INT', 'constraint' => '20', 'default' => '0')
			);
			$this->dbforge->add_column('posts', $fields);
        }

        public function down()
        {
                
        }
}
