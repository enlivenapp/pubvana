<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
	This migration adds database support to track 
	contact forms through the website.
*/

class Migration_contacts extends CI_Migration {

 
        public function up()
        {
        	// drop the contacts tracking table if it already exists.
        	$this->dbforge->drop_table('contacts', TRUE);


        	$this->dbforge->add_field(array(
				'id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
					'unsigned'       	=> TRUE,
					'auto_increment' 	=> TRUE
				),
				'name' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '20',
				),
				'email' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50',
				),
				'sender_ip' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50',
				),
				'message' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '200',
				)
			));

        	// CI doesn't really like default CURRENT_TIMESTAMP, so do the workaround... 'cause Narf says so.
			$this->dbforge->add_field("send_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

			// just say NO to MyISAM
        	$attributes = array('ENGINE' => 'InnoDB');

        	// add primary key to 'id'
			$this->dbforge->add_key('id', TRUE);

			// create the table and let's get to it.
			$this->dbforge->create_table('contacts', FALSE, $attributes);

                
        }

        public function down()
        {
        	$this->dbforge->drop_table('contacts', TRUE);
        }
}
