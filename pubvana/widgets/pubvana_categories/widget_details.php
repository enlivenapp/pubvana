<?php

class Widget_Pubvana_categories
{


	/*
			This file is loaded and the information
			is inserted into the database.  The only
			time this file is accessed is during 
			installation of the widget and when updating
			the widget.

			VERSION NUMBER MUST CHANGE FOR CHANGES TO TAKE EFFECT
		 */


		/* 
		 Name of your theme. 
		*/
		public $name = 'Pubvana Categories';

		/*
		  The description of your theme.  (200 chars max)
		 */
		public $description = 'Display blog categories on your website. Admin -> Categories';

		// The author of the theme.
		public $author = 'Enliven Applications';

		// the author's email address
		public $author_email = 'info@pubvana.org';

		// the author's email address
		public $author_website = 'https://enlivenapp.com';

		/* 
			Enter the version of your widget. We use this
			in the blog and on pubvana.org to determine
			if an update in available.
		*/
		public $version = '1.0.0';

		/*
			Options are a multi-dimensional array that lists
			the options that allows users to configure the widget. 
			
			[primary array]
			In the below example we use 'option1_*', 'option2_*', etc
			This can be anything as long as it's db and array key friendly
			IE: 'this_is_ok', 'thisIsOk', 'Not OK', etc... 

			[sub array]
			field_type 	= text or dropdown
			default 	= the default value (number, text, etc)
			label   	= Label text to show user
			help_text  	= text to help user with that field
			options    	= a pipe '|' delimited string of options (field_type = dropdown only)

			An empty array means there are no options for the
			user to configure.  When there are options the below
			formatting guidelines *must* be used.

			array(
				'option1_text_type'	=>
						array(
							'field_type' 	=> 'text',
							'default'	 	=> 'default text',
							'label'			=> 'Label Text',
							'help_text'		=> 'Help text for the user',
							'options'		=> ''
						);
				'option2_dropdown_type'	=>
						array(
							'field_type' 	=> 'dropdown',
							'default'	 	=> 'default text',
							'label'			=> 'Label Text',
							'help_text'		=> 'Help text for the user',
							'options'		=> '1|5|10'
						);
			);
		*/
		public $options = [
			'numcats'	=>
						[
							'field_type' 	=> 'dropdown',
							'default'	 	=> '5',
							'label'			=> 'Number of Categories',
							'help_text'		=> 'Choose the maximum number of categories to display publicly.',
							'options'		=> '1|3|5|7|9|10|15|20'
						],
					];

		/*
			This is potential content to be displayed on the public pages.
			This can be left blank or some type of example text can be place
			here.  HTML or plain text is ok.
		*/
		public $content = '';
}
