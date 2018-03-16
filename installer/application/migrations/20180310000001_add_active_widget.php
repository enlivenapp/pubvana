<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_active_widget extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'active'             => array('type' => 'int', 'constraint' => '1', 'default' => '0')  
			);
			$this->dbforge->add_column('widget_instances', $fields);


        }

        public function down()
        {
                
        }
}
