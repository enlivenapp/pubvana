<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_ganalytics extends CI_Migration {
 
        public function up()
        {

	        $insert_ganalytics = array(
					'name' 			=> 'gAnalyticsPropId',
					'value' 		=> '',
					'tab' 			=>	'google',
					'field_type' 	=> 'text',
					'options' 		=> '',
					'required' 		=> '0'
				);
			$this->db->insert('settings', $insert_ganalytics);


			$this->db->where('name', 'recaptcha_private_key')->update('settings', array('tab' => 'google'));
			$this->db->where('name', 'recaptcha_site_key')->update('settings', array('tab' => 'google'));
			$this->db->where('name', 'use_recaptcha')->update('settings', array('tab' => 'google'));

        }

		


        public function down()
        {
                
        }
}
