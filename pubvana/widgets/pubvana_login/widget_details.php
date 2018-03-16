<?php

class Widget_Pubvana_login
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
		public $name = 'Login';

		/*
		  The description of your theme.  (200 chars max)
		 */
		public $description = 'Widget to login to your website.  This widget disappears when user is logged in.';

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
		public $version = '1.0.1';

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
					'lang_login_btn' => [
										'field_type' 	=> 'text',
										'default'	 	=> 'Login',
										'label'			=> 'Login Button',
										'help_text'		=> 'Change the language used for the login button.',
						],
					'lang_remember_btn' => [
										'field_type' 	=> 'text',
										'default'	 	=> 'Remember Me',
										'label'			=> 'Remember Me Text',
										'help_text'		=> 'Change the language used for Remember Me checkbox.',
						],
					'lang_forgot_btn' => [
										'field_type' 	=> 'text',
										'default'	 	=> 'Forgot Your Password?',
										'label'			=> 'Forgotten Password Button',
										'help_text'		=> 'Change the language used for the Forgotten Password link.',
						],
					'lang_place_ident' => [
										'field_type' 	=> 'text',
										'default'	 	=> 'you@example.com',
										'label'			=> 'Identity Placeholder',
										'help_text'		=> 'Change the language used for the Email Placeholder.',
						],
					'lang_pass_ident' => [
										'field_type' 	=> 'text',
										'default'	 	=> 'Password',
										'label'			=> 'Password Placeholder',
										'help_text'		=> 'Change the language used for the Password Placeholder.',
						],

				];

		/*
			This is potential content to be displayed on the public pages.
			This can be left blank or some type of example text can be place
			here.  HTML or plain text is ok.
		*/
		public $content = '';
}
