<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_widget_options extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'options' => array('type' => 'text', 'null' => TRUE),
                                'content' => array('type' => 'text', 'null' => TRUE)
			);
			$this->dbforge->add_column('widgets', $fields);
        }

        public function down()
        {
                
        }
}
