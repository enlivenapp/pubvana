<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_widget_slug extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'slug' => array('type' => 'varchar', 'constraint' => '30')
			);
			$this->dbforge->add_column('widgets', $fields);
        }

        public function down()
        {
                
        }
}
