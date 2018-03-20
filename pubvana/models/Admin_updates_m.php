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

		$uploads_dir = realpath("uploads");
		$tmp_dir = $uploads_dir . '/tmp/';

		// if cURL/ZipArchive isn't installed 
		if (! $this->_isCurl() || ! $this->_isZipArchive())
		{
			$this->session->set_flashdata('error', lang('updates_curlzip_not_avail'));
			log_message('error', 'Updates: cURL or ZipArchive is not available.');
			return false;
		}

		// if we can't write to uploads/ fail
		if (!is_writable($uploads_dir))
		{
			$this->session->set_flashdata('error', lang('updates_uploads_not_write'));
			log_message('error', 'Updates: uploads/ is not writable');
			return false;
		}

		// we can write to the uploads/ dir, but uploads/tmp doesn't exist
		if (! is_dir($tmp_dir))
		{
			// make that dir
			if (! mkdir($tmp_dir, 0777))
			{
				$this->session->set_flashdata('error', lang('updates_update_failed_resp'));
				log_message('error', 'Updates: Could not mkdir tmp/ directory');
				return false;
			}
		}

		// if we failed to unlink last time, try again.
		if (file_exists($tmp_dir . 'current.zip'))
		{	
			// if it won't unlink, fail
			if (!unlink($tmp_dir . 'current.zip'))
			{
				$this->session->set_flashdata('error', lang('updates_update_failed_resp'));
				log_message('error', 'Updates: Could not unlink() previous current.zip file.');
				return false;
			}
		}

		// we should now have uploads/tmp with no current.zip file, otherwise, 
		// it's failed somewhere along the way.

   		// get the current.zip file
   		$download_url = $this->config->item('pv_update_download_url');

		// set up cURL
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $download_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if (! $data = curl_exec ($ch))
		{
			log_message('error', 'Updates: cURL Error: ' . curl_error($ch));
			return false;
		}
		curl_close ($ch);

		// define the file, we unlink later
		$destination = $tmp_dir . "current.zip";

		// open the file
		$file = fopen($destination, "w+");

		// it puts the data in the file or it gets the hose again
		fputs($file, $data);

		fclose($file);
		
		// new zip instance 
		$zip = new ZipArchive;

		// unzip the file
		$res = $zip->open($tmp_dir . 'current.zip'); 

		if ($res === TRUE) 
		{
			// extract to home directory
		    $zip->extractTo(FCPATH); 
		    $zip->close();

		    // now, for some fun...
		    // delete install files and 
		    // uploads/tmp directory
		    if (!$this->delDir($tmp_dir))
		    {
		    	// we're not going to outright fail, because the update worked
		    	// but we are going to log it as an error.
		    	$this->session->set_flashdata('error', lang('updates_update_failed_resp'));
		    	log_message('error', 'Updates: Unable to delete ' . $tmp_dir);
		    }

		    // let's try to update composer if it's installed locally, otherwise, they'll
		    // just have to rely on general updates.
		    if (file_exists(FCPATH . '/composer.phar'))
		    {
		    	passthru('php composer.phar update');
		    }
		    // success
		    return true;
		} 
		else 
		{
			// failed to process zip file
			$this->session->set_flashdata('error', lang('updates_update_failed_resp'));
			log_message('error', 'Updates: Failed to process zip file. Error: ' . $res);
		    return false;
		}
	}

	/**
     * delDir
     *
     * Deletes a directory and all contents
     *
     * @access  public
     * @author  Phil Sturgeon ?
     * @version 3.0
     * 
     * @return  bool
     */
	private function delDir($dir = false)
	{
		if (!$dir)
		{
			return false;
		}

		$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);

		$files = new RecursiveIteratorIterator($it,
		             RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) 
		{
		    if ($file->isDir())
		    {
		        rmdir($file->getRealPath());
		    } else 
		    {
		        unlink($file->getRealPath());
		    }
		}
		if (rmdir($dir))
		{
			return true;
		}

		return false;
	}




	/**
     * _isCurl
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


	public function _isZipArchive()
	{
		return class_exists('ZipArchive');
	}

}
