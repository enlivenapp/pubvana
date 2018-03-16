<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Updates M
 * 
 * Admin Updates Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class Admin_updates_m extends CI_Model
{
	// Protected or private properties
	protected $_table;
	protected $_update_url;
	
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

		// Load needed models, libraries, helpers and language files
		$tables = $this->config->item('pubvana');
		$this->_table = $tables['tables'];

		$this->_update_url = $this->config->item('pv_updates_url');
	}

	/**
     * check_for_update
     * 
     * IF cURL is installed on the server...
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  array
     */
	public function check_for_update()
	{
		
		// if curl is installed, we'll try...
		if ($this->_isCurl())
		{
			// load the curl lib
			$this->load->library('curl');

			// we should get back something in json
			if ($release_version = $this->curl->simple_get($this->_update_url))
			{
				// if we did, decode and return the array
				return (array) json_decode($release_version);
			}
			else
			{
				// else... we failed...
				return ['status' => 'failed', 'message' => lang('updates_failed_connection')];
			}

		}
		//elseif (wget...)
	}

	/**
     * perform_update
     * 
     * not currently used.
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  bool
     */
	public function perform_update()
	{
		return false;
	}

	/**
     * Construct
     *
     * @access  public
     * @author  Phil Sturgeon ?
     * @version 3.0
     * 
     * @return  bool
     */
	public function _isCurl()
	{
    	return function_exists('curl_init');
	}

}
