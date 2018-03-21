<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Themes M
 * 
 * Admin Themes Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class Admin_themes_m extends CI_Model
{
	// Protected or private properties
	protected $_table;
	
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
		
		$tables = $this->config->item('pubvana');
		$this->_table = $tables['tables'];
	}

	/**
     * get_themes
     * 
     * gets all themes
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  object
     */
	public function get_themes()
	{
		// load the helper file
		$this->load->helper('file');

		// get the list of 'folders' which
		// just means we're getting a potential
		// list of new themes that's been installed
		// since we last looked at themes
		$folders = get_dir_file_info(APPPATH . 'themes/');

		// foreach of those folders, we're loading the class
		// from the theme_details.php file, then we pass it
		// off to the save_theme() function to deal with 
		// duplicates and insert newly added themes.
		foreach ($folders as &$folder)
		{
			// spawn the theme class...
			$details = $this->spawn_class($folder['relative_path'], $folder['name']);

			// if spawn_class was a success
			// we'll see if it needs saving/updating
			if ($details)
			{
				// because this pwnd me for 30 minutes
				$details->path = $folder['name'];
			
				// save/update it...
				$this->save_theme($details);
			}
		}
		// now that we've updated everything, let's
		// give the end user something to look at.
		return $this->db->get($this->_table['templates'])->result();
	}

	/**
     * get_theme_by_id
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  null
     */
	public function get_theme_by_id($id)
	{
		return $this->db->where('id', $id)->limit(1)->get($this->_table['templates'])->row();
	}

	/**
     * activate_new_theme
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  bool
     */
	public function active_new_theme($new_theme)
	{
		// first we need to get the currently active theme
		$old_theme = $this->db->where('is_active', 1)->where('is_admin', $new_theme->is_admin)->limit(1)->get($this->_table['templates'])->row();

		// now we have the new and the old.
		// let's activate the new one and deactivate
		// the old one.
		$data = [
			// the old
   			[
		      'id' 			=>  $old_theme->id,
		      'is_active' 	=> 0
   			],
   			// and the new
   			[
		      'id' 			=> $new_theme->id,
		      'is_active' 	=> 1
   			]
		];

		return $this->db->update_batch($this->_table['templates'], $data, 'id');
	}



	/**
	 * Spawn Class
	 *
	 * Checks to see if a theme_details.php exists and returns 
	 * a class if it does
	 * 
	 * @author  Phil Sturgeon ?
	 *
	 * @param $path The path to the current folder
	 * @param  $dir The directory in application/themes/...
	 *
	 * @return array
	 */
	private function spawn_class($path, $dir)
	{
		// get the details of the theme
		$theme_details = $path . $dir . '/theme_details.php';

		// Check if the details file exists
		if ( ! is_file($theme_details))
		{
			return false;
		}

		// found it, now include it
		include_once $theme_details;

		// build the class
		$class = 'Theme_'.ucfirst(strtolower($dir));

		// returm class or fail
		return class_exists($class) ? new $class : false;
	}

	/**
     * save_theme
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  bool
     */
	private function save_theme($data)
	{
		// is this particular entry already
		// in the database?
		$exists = $this->db->where('path', $data->path)->limit(1)->from($this->_table['templates'])->count_all_results();

		$has_options = '0';

		if (!empty($data->options))
		{
			$has_options = '1';
		}

		//not in db yet, let's insert it.
		if ($exists == 0)
		{
			
			// build the insert
			$insert = [
				'name' 			=> $data->name,
				'description'	=> $data->description,
				'author'		=> $data->author,
				'author_email'	=> $data->author_email,
				'path'			=> $data->path,
				'image'			=> $data->path . '.png',
				'is_default'	=> '0',
				'is_active'		=> '0',
				'is_admin'		=> $data->is_admin,
				'version'		=> $data->version,
				'has_options'	=> $has_options
			];

			// execute and return.
			$inserted = $this->db->insert($this->_table['templates'], $insert);

			// get the ID of that db insert
			$inserted_id = $this->db->insert_id();

			// i hate doing the same twice, but needed to in this case
			// since I need the insert ID to make things easier.
			if (!empty($data->options))
			{
				// add the new options
				$this->add_options($data->options, $inserted_id);
			}

			// return
			return $inserted;
		}

		// It's in the db, do we need to update the theme?
		elseif ($exists == 1)
		{
			// first get the info on the theme
			$theme = $this->db->where('path', $data->path)->limit(1)->get($this->_table['templates'])->row();

			// Did the version change?  If so, we'll update the options table
			if ($theme->version != $data->version)
			{
				// update theme info
				$this->update_theme_info($theme->id, $data, $has_options);
				// do the options update
				return $this->auto_update_options($data->options, $theme->id);
			}


		}
	}


	/**
     * auto_update_options
     *
     * compares current options to those listed in the theme_details.php file, then adds or removes 
     * options in the database as required.
     *
     * The easy way to do this would be to delete everything in the options table and add what's in
     * the $opts array, however that will delete all the user's current settings and restore to 
     * default, which is less than ideal in most cases. 
	 *
	 * options come in as a multidimensional array
	 *		[
	 *		'widget_areas'	=> ['widget_area_1', 'widget_area_2'],
	 *		'images'		=> ['main_bg' => 'background-2000x1333.jpg']
	 *	];
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  bool
     */
	public function auto_update_options($opts, $themeId)
	{
		if (! $opts)
		{
			return;
		}
		// first get the info on the theme
		$theme = $this->db->where('id', $themeId)->limit(1)->get($this->_table['templates'])->row();

		// get the current options in the database
		$current = $this->db->where('theme_id', $themeId)->get($this->_table['theme_options'])->result_array();

		// foreach through comparing the current to options listed in the theme_details.php file.
		// At the end, anything in $current needs to be deleted, anything in $opts needs to be added.
		foreach ($opts as $optk => $optv)
		{
			if ($optk != 'widget_areas')
			{
				// now we have $optv which is an array so we need to 
				// loop through that and compare to each $current array item
				foreach ($current as $curk => $curv)
				{
					if (isset($optv[$curv['name']]))
					{
						unset($opts[$optk][$curv['name']]);
						unset($current[$curk]);
					}
				}
			}
			elseif ($optk == 'widget_areas')
			{
				// get the widgets and unset from $opts array
				// this'll leave use a clean array to insert new
				// options
				$widget_areas = $opts['widget_areas'];
				unset($opts['widget_areas']);

				$this->auto_update_widget_areas($widget_areas, $theme->id);
			}	
		}

		// in with the new
		if ($opts)
		{
			$this->add_options($opts, $themeId, false);
		}

		// out with the old
		if ($current)
		{
			foreach ($current as $cur)
			{
				$this->db->delete($this->_table['theme_options'], ['id' => $cur['id']]);
			}
		}
		return;
	}

	/**
     * Auto Update Widgets
     *
     * Automatically updates the widget areas in the database. Also will delete 
     * any widget areas that have been removed from theme_details.php.
     * 
     * EXAMPLE incoming array = ['widget_area_1', 'widget_area_2']
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
     * @return  bool
     */
	public function auto_update_widget_areas($widget_areas, $themeId)
	{
		if (empty($widget_areas))
		{
			return;
		}

		$insert = [];

		// get the current list of widget areas
		$current = $this->db->where('theme_id', $themeId)->get($this->_table['widget_areas'])->result_array();

		// loop through each $widget_areas list and see if
		// it's already in the database.
		foreach ($widget_areas as $wak => $wav)
		{
			foreach($current as $curk => $curv)
			{
				// if it's already in the database we don't need to insert it
				if ($wav == $curv['name'])
				{
					unset($widget_areas[$wak]);
					unset($current[$curk]);
				}
			}
		}

		// Now, if we have anything in this array
		// we add it to the database 
		if ($widget_areas)
		{
			foreach ($widget_areas as $wak => $wav)
			{
				$insert[] = [
						'name'			=> $wav,
						'theme_id'		=> $themeId
				];
			}
			$this->db->insert_batch($this->_table['widget_areas'], $insert);
		}

		// if there's anything left in $current, then it's likely been removed
		// from the theme_details.php file and no longer called, so we'll remove it.
		if ($current)
		{
			foreach ($current as $cur)
			{
				$this->db->where('id', $cur['id'])->delete($this->_table['widget_areas']);
			}
		}
	}


	/**
     * add_options
     *
     * adds theme options to theme_options table
     * This will also work well for a 'reset to default' function
	 *
	 * options come in as a multidimensional array
	 *		[
	 *		'widget_areas'	=> ['widget_area_1', 'widget_area_2'],
	 *		'images'		=> ['main_bg' => 'background-2000x1333.jpg']
	 *	];
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  null
     */
	public function add_options($opts, $themeId, $delete = true)
	{
		// first get the info on the theme
		$theme = $this->db->where('id', $themeId)->limit(1)->get($this->_table['templates'])->row();

		if ($delete === true)
		{
			// make sure there's no residual stuff from this theme in the options database
			$this->db->where('theme_id', $theme->id)->delete($this->_table['theme_options']);
		}
		

		// default empty array
		$insert_options = [];

		// widgets are a special case
		$insert_widgets = [];

		foreach ($opts as $optk => $optv)
		{
			if ($optk != 'widget_areas')
			{
				foreach ($optv as $optvk => $optvv)
				{
					$insert_options[] = [
								'name' 		=> $optvk,
								'value'		=> $optvv,
								'theme_id'	=> $theme->id,
								'type'		=> $optk
								];
				}
			}
			elseif ($optk == 'widget_areas')
			{
				// add widget areas to the database
				foreach($optv as $widget)
				{
					$insert_widgets[] = [
						'name'			=> $widget,
						'theme_id'		=> $theme->id
					];
				}
			}		
		}

		if ($insert_options)
		{
			$this->db->insert_batch($this->_table['theme_options'], $insert_options);
		}
		if ($insert_widgets)
		{
			$this->db->insert_batch($this->_table['widget_areas'], $insert_widgets);
		}
		
	}



	public function update_theme_info($themeId, $data, $hasOptions = '0')
	{
		if (! $data)
		{
			return false;
		}

		$update = [
				'name' 			=> $data->name,
				'description'	=> $data->description,
				'author'		=> $data->author,
				'author_email'	=> $data->author_email,
				'path'			=> $data->path,
				'image'			=> $data->path . '.png',
				'is_admin'		=> $data->is_admin,
				'version'		=> $data->version,
				'has_options'	=> $hasOptions
			];

		return $this->db->where('id', $themeId)->update($this->_table['templates'], $update);
	}



	public function get_theme_options($id)
	{

		return $this->db->where('theme_id', $id)->get($this->_table['theme_options'])->result();
	}



	public function admin_update_options($data, $theme_id)
	{
		foreach ($data as $optk => $optv)
		{
			$this->db->where('theme_id', $theme_id);
			$this->db->where('name', $optk);
			if (! $this->db->update($this->_table['theme_options'], ['value' => $optv]))
			{
				return false;
			}
		}
		return true;
		

	}





}
