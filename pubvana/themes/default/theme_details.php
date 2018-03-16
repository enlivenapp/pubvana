<?php

	class Theme_default
	{
		/*
			This file is loaded and the information
			is inserted into the database.  The only
			time this file is accessed is duing 
			installation of the theme and when updating
			the theme.
		 */


		/* 
		 Name of your theme. 
		*/
		public $name = 'Default Pubvana Theme';

		/*
		  The description of your theme.  
		 */
		public $description = 'The default theme for Pubvana';

		// The author of the theme.
		public $author = 'Enliven Applications';

		// the author's email address
		public $author_email = 'info@pubvana.org';

		/*
			Is this theme a replacement for the default admin theme?
			if so, enter a value of '1', otherwise, set to '0' to 
			use as a publicly facing theme.
		 */
		public $is_admin = '0';

		/* 
			Enter the version of your theme. We use this
			in the blog and on open-blog.org to determine
			if an update in available.
		*/
		public $version = '1.0.0';

			/*
			Enter the information in which you allow Widget areas,
			and other options this theme has available.  We highly recommend using theme options 
			for layout purposes as it allows users to customize their sites without editing HTML
			code on their own.

			Leave as blank array if there are no options. (see examples below)

			Widgets: must be named 'widget_area_' then a number.  IE: widget_area_1, widget_area_2, etc.

			Images: We attempt to find images (such as background images)  in two places. 
					1: in /themes/[theme_name]/img/[image_name] IF there is only a file name. IE: background.jpg
					2: use a complete URL (IE: http://example.com/images/someimage.jpg) 
				Note: #2 allows use of the media manager to upload and use custom images

				How to use: images are part of a multidimensional array.  simply declare the images in the 
							'images' sub array area.  In your theme call the image thusly:
							1: as img src:	<img src="<?= $this->template->[array_key] ?>" alt="<?php echo $template['title']; ?>">
							2: or as a style element: <div style="background-image: url('<?= $this->template->[array_key] ?>');"> </div>

							Example: $this->template->main_background  (see array example below)

				Note: Use full words with underscores for your array keys as we humanize them in the form <label> and placeholder="" however, you are limited to 50 characters

			EXAMPLE USES 

				(with options):
				public $options = 
						[
							// a simple array of widget areas you support in your theme
							'widget_areas'	=> ['widget_area_1', 'widget_area_2'],

							// an array of key/value pairs for images/videos/links you call in your theme
							'images'		=> ['main_background' => 'background-2000x1333.jpg']
							
						];

				(without options)
				public $options = [
						'widget_areas'	=> [],
						'images'		=> []
				];
		*/
		public $options = 
		[
			'widget_areas'	=> [],
			'images'		=> []
			
		];



}
