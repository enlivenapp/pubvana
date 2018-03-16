<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_comment_system extends CI_Migration {
 
        public function up()
        {

	        $insert_comment_system = array(
					'name' 			=> 'comment_system',
					'value' 		=> 'local',
					'tab' 			=>	'comments',
					'field_type' 	=> 'dropdown',
					'options' 		=> 'local=Local|fb=Facebook',
					'required' 		=> '1',
					'order_by'		=> 0
				);
			$this->db->insert('settings', $insert_comment_system);
        }

        public function down()
        {
                
        }
}



