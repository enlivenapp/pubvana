<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_navigation extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'parent_id' => array('type' => 'int', 'constraint' => '5', 'default' => '0')
			);
			$this->dbforge->add_column('navigation', $fields);


        }

        public function down()
        {
                
        }
}



