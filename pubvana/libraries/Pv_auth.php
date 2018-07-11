<?php
/**
 * Name:    Pub Auth
 * Author:  Enliven Applications
 *           mw@elivenapp.com
 *           @enlivenapp
 *
 * Created:  Jan 11, 2018
 *
 * Description:  Extension of Ion Auth's core functionality for Pubvana adding permissions and other useful methods.
 *
 * Requirements: PHP5 or above
 *
 * @package    Pv_auth
 * @author     Enliven Applications
 * @link       http://enlivenapp.com
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Pub Auth
 */
class Pv_auth extends Ion_auth {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('pv_auth_model');
	}


	public function get_db_display_name($id)
	{
		$query = $this->db->select('first_name, last_name')->where('id', $id)->limit(1)->get('users')->row();

		$display_name = $query->first_name . ' ' . $query->last_name;

		return $display_name;
	}


		/**
	 * get_permissions_dropdown
	 * 
	 * Builds permissions links (for navigation)
	 *
	 * @author Enliven Applications
	 * 
	 * @param   $dash for the admin dashboard? true|false
	 * 
	 * @return array
	 **/
	public function get_permissions_dropdown($dash=false)
	{
		if ($this->logged_in())
		{
			// this works for admin and non-admin alike
		// we'll get what they can do...
		return $this->pv_auth_model->get_users_perms(null, $dash);
		}
		return false;
	}



	/**
	 * get_display_name
	 *
	 * @return string
	 * @author Enliven Applications
	 **/
	public function get_display_name()
	{
		$display_name = $this->session->userdata('display_name');
		if ( ! empty($display_name) )
		{
			return $display_name;
		}
		return null;
	}


	/**
	 * get_first_name
	 *
	 * @return string
	 * @author Enliven Applications
	 **/
	public function get_first_name()
	{
		$first_name = $this->session->userdata('first_name');
		if ( ! empty($first_name) )
		{
			return $first_name;
		}
		return null;
	}


	/**
	 * get_last_name
	 *
	 * @return string
	 * @author Enliven Applications
	 **/
	public function get_last_name()
	{
		$last_name = $this->session->userdata('last_name');
		if ( ! empty($last_name) )
		{
			return $last_name;
		}
		return null;
	}



	/**
	 * get_username
	 *
	 * @return string
	 * @author Enliven Applications
	 **/
	public function get_username()
	{
		$user_id = $this->session->userdata('username');
		if ( ! empty($username) )
		{
			return $username;
		}
		return null;
	}

	/**
	 * get_user_id
	 *
	 * @return integer
	 * @author jrmadsen67
	 **/
	public function get_user_id()
	{
		$user_id = $this->session->userdata('user_id');
		if (!empty($user_id))
		{
			return $user_id;
		}
		return null;
	}

	/**
	 * Has Permission
	 *
	 * @author Enliven Applications
	 * 
	 * @param   $perm The name of permission being checked
	 * 
	 * @return bool
	 **/
	public function has_permission($perm)
	{
		// if they're not logged in
		// bounce'm
		if (! $this->logged_in())
		{
			return false;
		}

		// if they are an admin, they can
		// do anything
		if ($this->is_admin())
		{
			return true;
		}

		// the user can be in multiple groups, so we'll 
		// check them all.  we return true on the first
		// one we find
		foreach ($this->pv_auth_model->get_users_groups()->result() as $group)
		{
			// logged in, but not admin
			if ($this->pv_auth_model->check_perm($perm, $group->id))
			{
				return true;
			}
		}
		// didn't find any, bounce'm
		return false;
	}




}
