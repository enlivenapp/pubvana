<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_widgets_perms extends CI_Migration {
 
        public function up()
        {

        	// add media manager to the dashboard
	        $insert_widgets = array(
					'name' 			=> 'widgets',
					'description' 	=> 'Widgets',
					'protected' 	=>	'1',
					'display_order'	=> '15'
				);
			$this->db->insert('group_permissions', $insert_widgets);
        }

        public function down()
        {
                
        }
}
