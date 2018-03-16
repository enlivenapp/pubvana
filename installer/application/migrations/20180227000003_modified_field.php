<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_modified_field extends CI_Migration {
 
        public function up()
        {
			$fields = array(
			        'date_modified' => array('type' => 'DATE')
			);
			$this->dbforge->add_column('pages', $fields);
			$this->dbforge->add_column('posts', $fields);

        }

		


        public function down()
        {
                
        }
}
