<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Widgets Class
 *
 * Adds widgets to your website
 *
 * @package			CodeIgniter
 * @subpackage		Libraries
 * @category		Libraries
 * @author			Enliven Applications
 * @license			MIT
 */
class Widgets
{
	private $_ci;

	public $_widgets_path;

	protected $_theme;

	protected $_table;

	public $_slug;

	/* 
		NOT CURRENTLY USED
		allows users to set layout based on 
		the front end they're using 
		Options: bootstrap3, bootstrap3, material
	 	must be in widgets/[widget_dir]/views/[$front_framework]/[$view].php
	*/
	public $_front_framework = 'bootstrap3';




	/**
	 * Constructor - Sets Preferences
	 *
	 * The constructor can be passed an array of config values
	 */
	function __construct($config = array())
	{
		$this->initialize($config);

		log_message('debug', 'Widgets Class Initialized');
	}




	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			$this->{'_' . $key} = $val;
		}

		
		if ($this->_widgets_path == '')
		{
			$this->_widgets_path = APPPATH . 'widgets/';
		}

		$this->load->library('pvcore');
		// get theme info
		$this->_theme = $this->pvcore->get_active_theme();

		
		// get table names from config
		$tables = $this->config->item('pubvana');
		$this->_table = $tables['tables'];
	}

	// called from the public controller to 
	// set widget areas content as defined 
	// in the template/theme
	public function get_widgets()
	{
		$areas = $this->db
						->where('theme_id', $this->_theme->id)
						->get($this->_table['widget_areas'])
						->result();

		foreach ($areas as &$area)
		{
			$area->instances = $this->db
									->select($this->_table['widget_instances']. '.*, ' . $this->_table['widgets'] . '.slug')
									->where($this->_table['widget_instances'] . '.widget_area_id', $area->id)
									->where($this->_table['widget_instances'] . '.active', '1')
									->order_by($this->_table['widget_instances'] . '.order')
									->join($this->_table['widgets'], $this->_table['widgets'] . '.id = ' . $this->_table['widget_instances'] . '.widget_id')
									->get($this->_table['widget_instances'])
									->result();

			foreach ($area->instances as $i_key => $i_val)
			{
				$i_opts = unserialize($i_val->options);

				if ($i_opts)
				{
					foreach ($i_opts as $w_key => $w_val)
					{
						//$area->instances->$i_val = new stdClass;
						$area->instances[$i_key]->$w_key = $w_val['default'];
					}

					unset($area->instances[$i_key]->widget_area_id);
					unset($area->instances[$i_key]->widget_id);
					unset($area->instances[$i_key]->order);
					unset($area->instances[$i_key]->active);
					unset($area->instances[$i_key]->options);

					$this->_slug = $area->instances[$i_key]->slug;


					if ($class = $this->spawn_class($this->_widgets_path, $i_val->slug, $area->instances[$i_key]))
					{

						$area->instances[$i_key]->rendered = $class->run($area->instances[$i_key]);
					}
					else
					{
						$area->instances[$i_key]->rendered = false;
					}


				}
				else
				{
					if ($class = $this->spawn_class($this->_widgets_path, $i_val->slug, $area->instances[$i_key]))
					{
							// no options, just run it
						unset($area->instances[$i_key]->widget_area_id);
						unset($area->instances[$i_key]->widget_id);
						unset($area->instances[$i_key]->order);
						unset($area->instances[$i_key]->active);
						unset($area->instances[$i_key]->options);

						$area->instances[$i_key]->rendered = $class->run($area->instances[$i_key]);
					}
					else
					{
						$area->instances[$i_key]->rendered = false;
					}
				}
			}
		}

		// send it back to the public controller
		return $this->format_widgets($areas);
	}


	// format each widget for display
	public function format_widgets($areas = false)
	{
		if (! $areas)
		{
			return false;
		}

		$return;

		foreach ($areas as $area)
		{
			

			$return[$area->name] = '';
			$return[$area->name] .= '<div class="widget-main-container">';

			foreach ($area->instances as $instance)
			{

				if (trim($instance->rendered) == 'cancel')
				{
					$return[$area->name] .= '';
				}
				else
				{
					$return[$area->name] .= '<div class="widget-container" id="' . $instance->id . '">';

					if ($instance->show_title == 1)
					{
						$return[$area->name] .= '<div class="widget-title">';
						$return[$area->name] .= '<h4>' . $instance->title . '</h4>';
						$return[$area->name] .= '</div>';
					}

					if ($instance->rendered)
					{
						$return[$area->name] .= '<div class="widget-body">';
						$return[$area->name] .= $instance->rendered;
						$return[$area->name] .= '</div>';
					}

					if ($instance->content)
					{
						$return[$area->name] .= '<div class="widget-content">';
						$return[$area->name] .= ($this->isHTML($instance->content)) ? $instance->content : nl2br($instance->content);
						$return[$area->name] .= '</div>';
					}

					$return[$area->name] .= '</div>';
				}
			}
			$return[$area->name] .= '</div>';

		}
		return $return;
	}

	public function isHTML($string)
	{
		return $string != strip_tags($string) ? true : false;
	}



	public function render($view, $data = stdClass) {

		return $this->render_view($this->_widgets_path . $data->slug . '/views/' . $view, $data, false);
        //return $this->load->view($data->slug . '/views/' . 'display', $data);
    }






	public function render_view($view, $vars = array(), $get = TRUE) {
	
        //  ensures leading /
        if ($view[0] != '/') $view = '/' . $view;
        //  ensures extension   
        $view .= ((strpos($view, ".", strlen($view)-5) === FALSE) ? '.php' : '');
        //  replaces \'s with /'s
        $view = str_replace('\\', '/', $view);

        if (!is_file($view)) if (is_file($_SERVER['DOCUMENT_ROOT'].$view)) $view = ($_SERVER['DOCUMENT_ROOT'].$view);

        if (is_file($view)) {
            if (!empty($vars)) extract( (array) $vars);
            ob_start();
            include($view);
            $return = ob_get_clean();
            if ( $get ) 
            {
            	echo($return);
            }
            else
            {
            	return $return;
            } 
        }
        // file wasn't found...
        return false;
    }





	/**
	 * __get
	 *
	 * Enables the use of CI super-global without having to define an extra variable.
	 *
	 * I can't remember where I first saw this, so thank you if you are the original author. -Militis
	 *
	 * @param    string $var
	 *
	 * @return    mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
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
	private function spawn_class($path, $slug, $args = false)
	{
		// get the details of the theme
		$widget_file = $path . $slug . '/' . ucfirst(strtolower($slug)) . '.php';

		// Check if the details file exists
		if ( ! is_file($widget_file))
		{
			return false;
		}

		// found it, now include it
		include_once $widget_file;

		// build the class
		$class = ucfirst(strtolower($slug));

		// returm class or fail
		return class_exists($class) ? new $class($args) : false;
	}






}  // EOC
