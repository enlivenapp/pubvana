<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_widgets_m
 * 
 * Admin Widgets Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 1.0
 * 
*/
class Admin_widgets_m extends CI_Model
{
	// Protected or private properties
	protected $_table;

	protected $_theme;

	protected $_widget_locations;
	
	/**
     * Construct
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
     * @return  null
     */
	public function __construct()
	{
		parent::__construct();
		
		// get table names from config
		$tables = $this->config->item('pubvana');
		$this->_table = $tables['tables'];

		// init the whole thing...
		$this->initialize();
	}

	/**
     * Initialize
     *
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
	 *
     * @return  null
     */
	function initialize()
	{
		$this->_theme = $this->db->where('is_active', 1)->where('is_admin', 0)->limit(1)->get($this->_table['templates'])->row();

		// Where are all the widgets?
		$widgets = glob(APPPATH . 'widgets/*', GLOB_ONLYDIR);

		// if there's none, empty array
		if ( ! is_array($widgets) )
		{
			$widgets = array();
		}

		/* if we have widgets, break them down
		 into an multi array we can work with
		 and store the path in _widget_locations

		 Array(
    		[hello_world] => /Users/enlivenapp/home/public_html/pubvana/widgets/hello_world/
    		[slug]		  => /path/
		)
		*/
		foreach ($widgets as $widget_path)
		{
			$slug = basename($widget_path);

			// Set this so we know where it is later
			$this->_widget_locations[$slug] = $widget_path . '/';
		}
	}

	/**
     * Get Widgets
     *
     * Gets widgets arranged in widget areas.
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
	 *
     * @return  object
     */
	public function get_widgets()
	{
		$data['widget_areas'] = $this->get_widget_areas();

		foreach ($data['widget_areas'] as &$area)
		{
			$area->active_widgets = $this->get_widget_instances($area->id);
		}

		return $data;
	}

	/**
     * Get Widgets List
     *
     * get a list of all available widgets
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 *
     * @return  object
     */
	public function get_widgets_list()
	{
		// make sure we have any newly installed widgets
		$this->auto_update_wigets();

		return $this->db->get($this->_table['widgets'])->result();
	}

	/**
     * Get Widget Instances
     *
     * get the list of widget instances for a particular widget area
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $areaId
	 *
     * @return  object|bool
     */
	public function get_widget_instances($areaId = false)
	{
		if (! $areaId)
		{
			return false;
		}

		$results = $this->db
						->select($this->_table['widget_instances'] . '.*, ' . $this->_table['widgets'] . '.name as widget_name')
						->where($this->_table['widget_instances'] . '.widget_area_id', $areaId)
						->join($this->_table['widgets'], $this->_table['widgets'] . '.id = ' . $this->_table['widget_instances'] . '.widget_id')
						->order_by($this->_table['widget_instances'] . '.order')
						->get($this->_table['widget_instances'])
						->result();

		if ($results)
		{
			foreach ($results as &$result)
			{
				if ($result->options)
				{
					$result->options = unserialize($result->options);
				}
			}

			return $results;
		}
		return false;
	}

	/**
     * Admin Update Instance
     *
     * Updates widget instance
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $data = $_POST data from controller
	 *
     * @return  bool
     */
	public function admin_update_instance($data = false)
	{
		if (!$data)
		{
			return false;
		}

		$id = $data['widget_instance'];
		unset($data['widget_instance']);
		
		if (isset($data['options']))
		{
			// get the old options so we can preserve options
			$db_opts =  $this->db->select('options')->where('id', $id)->limit(1)->get($this->_table['widget_instances'])->row();

			// need an array...
			$update_opts = unserialize($db_opts->options);

				foreach ($data['options'] as $k => $v)
				{
					// only update the value of default (IE the important bit)
					// the rest is set by the widget dev
					$update_opts[$k]['default'] = $v;
				}
			// back to serialized data
			$data['options'] = serialize($update_opts);
		}
		
		// update the database.
		if ($this->db->where('id', $id)->update($this->_table['widget_instances'], $data))
		{
			return true;
		}
		// no $data['options'] or db insert failed.
		return false;

	}

	/**
     * Update Instance Order
     *
     * Updates the widget instance order
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $post_data = $_POST
	 *
     * @return  bool
     */
	public function update_instance_order($post_data)
	{
		// no data? fail!
		if (! $post_data || ! $post_data['widget-instance-container'])
		{
			return false;
		}

		$return = [];

		$i = 0;

		// foreach through each item 
		foreach ($post_data['widget-instance-container'] as $key => $instanceId) {

            //  we have the database ID($id) and it's widget_area($widget_area)

			// If we tried and failed to update the db
			// we fail so they can try again
			if (! $this->db->where('id', $instanceId)->update($this->_table['widget_instances'], ['order' => $i]))
			{
				return false;
			}

			// iteration!
    		$i++;
		}

		// looks like everything went
		// well, return true.
		return true;
	}

	/**
     * Add Widget To Area
     *
     * Admin - Adds a widget instance to an widget area
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $widgetId
	 * @param $areaId
	 *
     * @return  bool
     */
	public function add_widget_to_area($widgetId = null, $areaId = null)
	{

		$last_widget = $this->db
			->select('`order`')
			->order_by('`order`', 'desc')
			->limit(1)
			->get_where($this->_table['widget_instances'], array('widget_area_id' => $areaId))
			->row();

		$order = isset($last_widget->order) ? $last_widget->order + 1 : 1;

		$widget = $this->db->limit(1)->where('id', $widgetId)->get($this->_table['widgets'])->row();

		$insert = [
			'widget_area_id'	=> $areaId,
			'widget_id'			=> $widgetId,
			'title'				=> '*** New *** ' . $widget->name . ' Widget',
			'show_title'		=> 0,
			'order'				=> $order,
			'options'			=> $widget->options,
			'content'			=> $widget->content
		];
		if ($this->db->insert($this->_table['widget_instances'], $insert))
		{
			return true;
		}


		return false;
	}


	/**
     * Get Widget Areas
     *
     * get the list of places we can put widgets
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
	 *
     * @return  obj
     */
	// 
	public function get_widget_areas()
	{
		return $this->db->where('theme_id', $this->_theme->id)->order_by('name')->get($this->_table['widget_areas'])->result();
	}



	/**
     * Auto Update Widgets
     *
     * update the widgets list/database so we have 
	 * all the newly installed widgets. Also updates 
	 * widget info when new version is released.
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 *
     * @return  null
     */
	public function auto_update_wigets()
	{
		foreach ($this->_widget_locations as $slug => $widg_path)
		{	
			// is it already in the db?
			$exists = $this->db->where('slug', $slug)->count_all_results($this->_table['widgets']);

			// nope - add it
			if ($exists == 0)
			{
				
				if ($class = $this->spawn_class($widg_path, $slug))
				{
					// populate the database with new info
					// called like: $class->var
					$insert = [
						'name'				=> $class->name,
						'description'		=> $class->description,
						'author'			=> $class->author,
						'author_email'		=> $class->author_email,
						'author_website'	=> $class->author_website,
						'version'			=> $class->version,
						'slug'				=> $slug,
						'options'			=> serialize($class->options),
						'content'			=> $class->content
					];

					$this->db->insert($this->_table['widgets'], $insert);

				}
				else
				{
					// log it
					log_message('error', 'Load Widget Class Failed - Tried to load ' . $widg_path . '/widget_details.php.  Does widget_details.php exist?');
				}

			}
			// it does exist, so we'll check for updates
			else
			{
				$widget = $this->db->where('slug', $slug)->limit(1)->get($this->_table['widgets'])->row();

				if ($class = $this->spawn_class($widg_path, $slug))
				{
					// is the file version higher than the 
					// one we have in the db?  If so, update,
					// if not, leave it alone and move on.
					if ($widget->version < $class->version)
					{
						log_message('info', 'Updated Widget - ' . $class->name);

						// update the database with new info
						$update = [
							'name'				=> $class->name,
							'description'		=> $class->description,
							'author'			=> $class->author,
							'author_email'		=> $class->author_email,
							'author_website'	=> $class->author_website,
							'version'			=> $class->version,
							'options'			=> serialize($class->options),
							'content'			=> $class->content
						];

						$this->db->where('slug', $slug)->update($this->_table['widgets'], $update);
					}
					

				}
				else
				{
					// log it
					log_message('error', 'Load & Update Widget Class Failed - Tried to load and update ' . $widg_path . '/widget_details.php.  Does widget_details.php exist?');
				}
			}
		}
	}



	/**
	 * Spawn Class
	 *
	 * Checks to see if a widget_details.php exists and returns 
	 * a class if it does.  This class only contains information
	 * on the widget and not any actual functionality.
	 * 
	 * @author  Phil Sturgeon ?
	 * @author  Enliven Applications
	 *
	 * @param $path The path to the current folder
	 * @param $slug The slug to add to class Widget_
	 *
	 * @return array
	 */
	private function spawn_class($path, $slug)
	{
		// get the details of the theme
		$widget_details = $path . '/widget_details.php';

		// Check if the details file exists
		if ( ! is_file($widget_details))
		{
			return false;
		}

		// found it, now include it
		include_once $widget_details;

		// build the class
		$class = 'Widget_'.ucfirst(strtolower($slug));

		// returm class or fail
		return class_exists($class) ? new $class : false;
	}


	/**
     * Remove Instance
     *
     * Removes a widget instance from the site.
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $instanceId
	 *
     * @return  bool
     */
	public function remove_instance($instanceId)
	{
		return ($this->db->delete($this->_table['widget_instances'],['id' => $instanceId])) ? true : false;
	}



}  // EOC
