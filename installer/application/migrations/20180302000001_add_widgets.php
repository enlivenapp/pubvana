<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
	This migration adds database support to track 
	contact forms through the website.
*/

class Migration_add_widgets extends CI_Migration {

 
        public function up()
        {

        	// just say NO to MyISAM
        	$attributes = array('ENGINE' => 'InnoDB');


        	// widget areas - called in $theme_id's theme
        	$this->dbforge->add_field(array(
				'id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
					'unsigned'       	=> TRUE,
					'auto_increment' 	=> TRUE
				),
				'name' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50'
				),
				'theme_id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11'
				)
			));

        	// add primary key to 'id'
			$this->dbforge->add_key('id', TRUE);

			// create the table and let's get to it.
			$this->dbforge->create_table('widget_areas', FALSE, $attributes);

 
			// widgets
			// holds the list of available widgets
        	$this->dbforge->add_field(array(
				'id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
					'unsigned'       	=> TRUE,
					'auto_increment' 	=> TRUE
				),
				'name' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '50'
				),
				'description' => array(
					'type'       		=> 'VARCHAR',
					'constraint' 		=> '200'
				),
				'author' => array(
					'type'           	=> 'VARCHAR',
					'constraint'     	=> '50'
				),
				'author_email' => array(
					'type'           	=> 'VARCHAR',
					'constraint'     	=> '50'
				),
				'author_website' => array(
					'type'           	=> 'VARCHAR',
					'constraint'     	=> '50'
				),
				'version' => array(
					'type'           	=> 'VARCHAR',
					'constraint'     	=> '50'
				)
			));

        	// add primary key to 'id'
			$this->dbforge->add_key('id', TRUE);

			// create the table and let's get to it.
			$this->dbforge->create_table('widgets', FALSE, $attributes);




			// widget instances
			// currently active widgets in the theme
        	$this->dbforge->add_field(array(
				'id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11',
					'unsigned'       	=> TRUE,
					'auto_increment' 	=> TRUE
				),
				'widget_area_id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11'
				),
				'widget_id' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '11'
				),
				'title' => array(
					'type'           	=> 'VARCHAR',
					'constraint'     	=> '50'
				),
				'show_title' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '1',
					'default'			=> '1'
				),
				'options' => array(
					'type'           	=> 'TEXT',
					'null'     			=> TRUE
				),
				'content' => array(
					'type'           	=> 'TEXT',
					'null'     			=> TRUE
				),
				'order' => array(
					'type'           	=> 'INT',
					'constraint'     	=> '1'
				)
			));

        	// add primary key to 'id'
			$this->dbforge->add_key('id', TRUE);

			// create the table and let's get to it.
			$this->dbforge->create_table('widget_instances', FALSE, $attributes);










                
        }

        public function down()
        {
        	$this->dbforge->drop_table('widget_areas', TRUE);
        	$this->dbforge->drop_table('widgets', TRUE);
        	$this->dbforge->drop_table('widget_instances', TRUE);
        }
}
