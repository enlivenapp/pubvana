<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_media_manager extends CI_Migration {
 
        public function up()
        {

        	// add media manager to the dashboard
	        $insert_media = array(
					'name' 			=> 'media',
					'description' 	=> 'Media',
					'protected' 	=>	'1'
				);
			$this->db->insert('group_permissions', $insert_media);

			// add display order so we can pretty up the dashboard links
			$fields = array(
			        'display_order' => array('type' => 'INT', 'constraint' => '5', 'unique' => TRUE)
			);
			$this->dbforge->add_column('group_permissions', $fields);

			// assign display_order to all current group_permissions items.
			$this->db->where('name', 'dashboard')->update('group_permissions', array('display_order' => '1'));
			$this->db->where('name', 'cats')->update('group_permissions', array('display_order' => '2'));
			$this->db->where('name', 'comments')->update('group_permissions', array('display_order' => '3'));
			$this->db->where('name', 'lang')->update('group_permissions', array('display_order' => '4'));
			$this->db->where('name', 'links')->update('group_permissions', array('display_order' => '5'));
			$this->db->where('name', 'media')->update('group_permissions', array('display_order' => '6'));
			$this->db->where('name', 'navigation')->update('group_permissions', array('display_order' => '7'));
			$this->db->where('name', 'pages')->update('group_permissions', array('display_order' => '8'));
			$this->db->where('name', 'posts')->update('group_permissions', array('display_order' => '9'));
			$this->db->where('name', 'settings')->update('group_permissions', array('display_order' => '10'));
			$this->db->where('name', 'social')->update('group_permissions', array('display_order' => '11'));
			$this->db->where('name', 'themes')->update('group_permissions', array('display_order' => '12'));
			$this->db->where('name', 'updates')->update('group_permissions', array('display_order' => '13'));
			$this->db->where('name', 'users')->update('group_permissions', array('display_order' => '14'));

        }

		


        public function down()
        {
                
        }
}

