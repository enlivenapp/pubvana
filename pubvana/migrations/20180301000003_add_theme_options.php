<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
	This migration adds database support to track 
	contact forms through the website.
*/

class Migration_add_theme_options extends CI_Migration {

 
        public function up()
        {

        	$this->dbforge->add_field(array(
				'id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
					'unsigned'       	=> TRUE,
					'auto_increment' 	=> TRUE
				),
				'theme_id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
				),
				'name' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50',
				),
				'value' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '200',
				),
				'type' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50',
				)
			));

			// just say NO to MyISAM
        	$attributes = array('ENGINE' => 'InnoDB');

        	// add primary key to 'id'
			$this->dbforge->add_key('id', TRUE);

			// create the table and let's get to it.
			$this->dbforge->create_table('theme_options', FALSE, $attributes);

                
        }

        public function down()
        {
        	$this->dbforge->drop_table('theme_options', TRUE);
        }
}
