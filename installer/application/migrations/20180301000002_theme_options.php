<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_theme_options extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'has_options' => array('type' => 'int', 'constraint' => '1', 'default' => '0')
			);
			$this->dbforge->add_column('templates', $fields);


        }

        public function down()
        {
                
        }
}
