<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_featured_post extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'featured' => array('type' => 'INT', 'constraint' => '1', 'default' => '0')
			);
			$this->dbforge->add_column('posts', $fields);
        }

        public function down()
        {
                
        }
}
