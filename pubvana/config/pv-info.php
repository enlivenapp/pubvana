<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

/*
	Most of this will end up in the database
	and be overwritten.  These are defaults.

 */

	/*
	Editing the information below can have
	significant negative side effects.

	Some of this information is used for
	the update process.
 */


$config['pv_version'] 	= '1.0.3';
$config['pv_author']	= 'Enliven Applications';
$config['pv_email']		= 'info@pubvana.org';


// API endpoints

// returns current release 
// version number
$config['pv_updates_url']			= 'https://updates.pubvana.org/current/';

// returns current release 
// version files
$config['pv_update_download_url']	= 'https://updates.pubvana.org/current/download';

// returns list of available themes 
// for the current release of OB.
$config['pv_themes_url']			= 'https://pubvana.org/addons/themes/';

// returns list of available themes 
// for the current release of OB.
$config['pv_widgets_url']			= 'https://pubvana.org/addons/widgets/';




/*
	How many results per page?
	This is used for any page that uses pagination.

	Default: 10
 */
$config['results_per_page'] = '10';


$config['pubvana']['tables']['posts']          				= 'posts';
$config['pubvana']['tables']['categories']     				= 'categories';
$config['pubvana']['tables']['tags']    					= 'tags';
$config['pubvana']['tables']['tags_to_post']  				= 'tags_to_post';
$config['pubvana']['tables']['links']    					= 'links';
$config['pubvana']['tables']['comments']    				= 'comments';
$config['pubvana']['tables']['users']    					= 'users';
$config['pubvana']['tables']['posts_to_categories']     	= 'posts_to_categories';
$config['pubvana']['tables']['settings']          			= 'settings';
$config['pubvana']['tables']['pages']          				= 'pages';
$config['pubvana']['tables']['navigation']        			= 'navigation';
$config['pubvana']['tables']['redirects']          			= 'redirects';
$config['pubvana']['tables']['templates']          			= 'templates';
$config['pubvana']['tables']['theme_options']          		= 'theme_options';
$config['pubvana']['tables']['social']          			= 'social';
$config['pubvana']['tables']['notifications']          		= 'notifications';
$config['pubvana']['tables']['languages']          			= 'languages';
$config['pubvana']['tables']['widget_areas']          		= 'widget_areas';
$config['pubvana']['tables']['widgets']          			= 'widgets';
$config['pubvana']['tables']['widget_instances']          	= 'widget_instances';


/*
 | Users table column and Group table column you want to join WITH.
 |
 | Joins from users.id
 | Joins from groups.id
 */
$config['join']['users']  									= 'user_id';
$config['join']['groups'] 									= 'group_id';


