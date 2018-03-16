<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_fbid extends CI_Migration {
 
        public function up()
        {

	        $insert_fbid = array(
					'name' 			=> 'facebook_id',
					'value' 		=> '',
					'tab' 			=>	'comments',
					'field_type' 	=> 'text',
					'options' 		=> '',
					'required' 		=> '0'
				);
			$this->db->insert('settings', $insert_fbid);
        }

		


        public function down()
        {
                
        }
}

