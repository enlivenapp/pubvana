<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PV_Controller
 * 
 * PV_Controller Controller Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class PV_Controller extends CI_Controller
{

	/**
     * Construct
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  null
     */
	public function __construct()
	{
		// here's where we'll autoload all the site specific stuff
		// from the database... 
		parent::__construct();

		//$this->output->enable_profiler($this->config->item('enable_profiler'));
		
		$this->benchmark->mark('pub_controller_start');

		// cache output for 5 minutes on public pages. Just in case someone gets 
		// super popular
		$this->output->cache(5);

		$this->load->library('pvcore');
		
		// Set default language. The user can
		// choose to overwrite session data or the
		// site owner can choose to use a different language
		if ( ! $this->session->language )
		{
			$this->pvcore->set_lang();
		}

		// we use this everywhere
		$this->load->library('ion_auth');
		$this->load->model('Blog_m');
		$this->load->language('blog', $this->session->language);

		// get theme info
		$theme = $this->pvcore->get_active_theme();

		// get all the settings from the db
		$settings = $this->pvcore->db_to_config();

		if ($this->config->item('site_name'))
		{
			$this->template->title($this->config->item('site_name'));
		}

		// google analytics
		if ($this->config->item('gAnalyticsPropId'))
		{
			$this->template->set_metadata('gAnalyticsPropId', $this->config->item('gAnalyticsPropId'), $type = 'ganalytics');
		}

		// because PITassets...
		Asset::set_url(base_url());
		Asset::add_path('core', base_url('pubvana/themes/' . $theme->path . '/'));
		$this->template->set_theme($theme->path);

		// set the theme options if there are any
		$theme_opts = $this->pvcore->set_active_theme_opts($theme->id);

		/*
			WIDGETS

			As with any other $this->template->set([var], [val]) you call it
			like $this->template->[var]

			Widgets are set to a template value for easy access.  These are
			pre-populated for you as specified by the user all you need to 
			do is call the specific var you created in $options array in the 
			theme_details.php file.
		*/
		$widgets = new Widgets();

		$widgetsToTemplate = $widgets->get_widgets();

		if ($widgetsToTemplate)
		{
			foreach ($widgetsToTemplate as $widgk => $widgv)
			{
				$this->template->set($widgk, $widgv);
			}
		}

		// let's set up default places for template partials.
		// all of these can be used or not as needed.
		$this->template
				->set_partial('nav', 'nav')
				->set_partial('archives', 'archives')
				->set_partial('categories', 'categories')
				->set_partial('notices', 'notices')
				->set_partial('links', 'links')
				->set_partial('social', 'social');

		$this->template
				->set('admin_nav', $this->pv_auth->get_permissions_dropdown())
				->set('lang_picker', $this->pvcore->get_lang_options())
				->set('nav', $this->pvcore->get_navigation())
				->set('archives_list', $this->Blog_m->get_archive())
				->set('links_list', $this->Blog_m->get_links())
				->set('categories_list', $this->Blog_m->get_categories())
				->set('social_list', $this->pvcore->generate_social_links());

		$this->benchmark->mark('pub_controller_end');
	}

}


/**
 * PV_AdminController
 * 
 * PV_AdminController Controller Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class PV_AdminController extends CI_Controller
{

	/**
     * Construct
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  null
     */
	public function __construct()
	{
		parent::__construct();

		// start benchmarking...
		$this->benchmark->mark('admin_controller_start');

		// no caching of pages for the admin area.  
		$this->output->cache(0);

		//$this->output->enable_profiler($this->config->item('enable_profiler'));
		
		// make sure the db is up to date
		// using automated migrations. This
		// is ONLY done in admin as it's a
		// potential security risk, so we'll
		// at least try to keep all that behind
		// a login.
		$this->load->library('migration');

        if ($this->migration->latest() === FALSE)
        {
                show_error($this->migration->error_string());
        }



		// load up the core library for
		// Open Blog
		$this->load->library('pvcore');

		// if we can't find a language set
		// we'll set one now
		if ( ! $this->session->language )
		{
			$this->pvcore->set_lang();
		}

		// load admin language
		$this->load->language('admin', $this->session->language);

		// we're always using this in the
		// admin area so we'll essentually autoload
		$this->load->library('ion_auth');

		// get admin theme info
		$theme = $this->pvcore->get_active_theme('1');

		// get all the settings from the db
		$settings = $this->pvcore->db_to_config();

		// roll the database setting into 
		// $this->config so we can use them
		if ($this->config->item('site_name'))
		{

			$title_parts[] = $this->config->item('site_name');

			// If the method is something other than index, use that
			if ($this->router->fetch_method() != 'index')
			{
				$title_parts[] = $this->router->fetch_method();
			}

			// Make sure controller name is not the same as the method name
			if ( ! in_array($this->router->fetch_method(), $title_parts))
			{
				$title_parts[] = $this->router->fetch_class();
			}

			// Glue the title pieces together using the title separator setting
			$title = humanize(implode(' | ', $title_parts));


			$this->template->title(ucwords($title), ' Powered by Pubvana');
		}


		// because PITassets...
		Asset::set_url(base_url());
		Asset::add_path('core', base_url('pubvana/themes/' . $theme->path . '/'));
		$this->template->set_theme($theme->path);

		// set some partials
		$this->template
				->set_partial('flashdata', 'flashdata')
				->set_partial('sidebar', 'sidebar');


		// installer warning default
		$this->template->set('installer_warning', FALSE);
 
		// if we find the /installer directory exists 
		// then throw the installer_warning
		if (is_dir('./installer'))
		{
			// override the default
			$this->template->set('installer_warning', lang('installer_dir_warning_notice'));
		}

		$this->template->set('admin_nav', $this->pv_auth->get_permissions_dropdown(true));

		// end benchmarking
		$this->benchmark->mark('admin_controller_end');

		// and we're off.....
	}
}

