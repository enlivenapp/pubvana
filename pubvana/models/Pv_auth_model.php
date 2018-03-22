<?php
/**
 * Name:    Pv Auth Model
 * Author:  Enliven Applications
 *           mw@enlivenapp.com
 *
 * Created:  1.26.2018
 *
 * Description:  Additional functionality for Pubvana
 *
 * Requirements: PHP5 or above
 *
 * @package    Pv-Auth
 * @author     Enliven Applications
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Pv Auth Model
 */
class Pv_auth_model extends Ion_auth_model
{

	public function __construct()
	{
		parent::__construct();
	}



	/**
	 * permission
	 *
	 * @return object
	 * @author Enliven Applications
	 **/
	public function permission($id = NULL)
	{

		if (isset($id))
		{
			$this->where($this->tables['permissions'].'.id', $id);
		}

		$this->limit(1);
		$this->order_by('id', 'desc');

		return $this->permissions();
	}


	/**
	 * user_permissions
	 * 
	 * Gets the user's permissions
	 * 
	 * @param  string $id user's id
	 * @param  bool $dash whether this is for the admin dashboard
	 * 
	 * @author  Enliven Applications
	 * @return  array
	 * 
	 */
	public function get_users_perms($id=null, $dash=false)
	{
		$this->trigger_events('get_users_perms');

		// if no id was passed use the current users id
		$id || $id = $this->session->userdata('user_id');

		$groups = $this->db->select($this->tables['users_groups'].'.'.$this->join['groups'].' as id, '.$this->tables['groups'].'.name, '.$this->tables['groups'].'.description')
		                ->where($this->tables['users_groups'].'.'.$this->join['users'], $id)
		                ->join($this->tables['groups'], $this->tables['users_groups'].'.'.$this->join['groups'].'='.$this->tables['groups'].'.id')
		                ->get($this->tables['users_groups'])
						->result();

	    
		// default empty array
		$result = [];

		
	    // now we have all the user's groups,
		// now sort the perms for each group
		// if a group is admin, we build them all.
		foreach ($groups as $group)
		{
			if ($group->name == 'admin')
			{
				// get all the things
				$all = $this->db->order_by('display_order', 'asc')->get($this->tables['permissions'])->result();

				// if $dash is true, we build dashboard links
				if ($dash)
	    		{
					return $this->build_perms_links_dash($all);
				}
				// else we build public admin links
				else
				{
					return $this->build_perms_links($all);
				}
			}
			// not an admin
			else
			{
				//so get their groups/perms
				$perms_arr[] = $this->db
									->where('group_id', $group->id)
									->join($this->tables['permissions'], $this->tables['groups_perms'] . '.perms_id = ' . $this->tables['permissions'] . '.id')
									->get($this->tables['groups_perms'])
									->result_array();
			}
		}

		// if we've gotten to here we're 
		// using the $perms_arr
		foreach ($perms_arr as $sub_arr) 
		{
			// ugh, merge ALL the arrays and then make
			// sure they're not listed twice...
    		$result = array_unique(array_merge($result, $sub_arr), SORT_REGULAR);
    	}

    	// if $dash is true, we build dashboard links
    	if ($dash)
    	{
    		return $this->build_perms_links_dash(json_decode(json_encode($result)));
    	}
    	// else we build public admin links
    	else
    	{
    		return $this->build_perms_links(json_decode(json_encode($result)));
    	}
	}


	/**
	 * build_perms_links_dash
	 * 
	 * Gets the user's permissions
	 * 
	 * @param  string $perms
	 * 
	 * @author  Enliven Applications
	 * @return  array
	 * 
	 */
	public function build_perms_links_dash($perms)
	{
		// default empty array
		$return_arr = [];

		// build the links
		foreach($perms as $k => $v)
		{
			// two odd cases, where I didn't necessarily stick to 
			// the normal convention I set...
			if ($v->name == 'settings' || $v->name == 'dashboard')
			{	
				// setting has a url of /admin/settings
				if ($v->name == 'settings')
				{
					$return_arr[$k]['link'] = '<a href="' . site_url('admin/' . $v->name) . '">' . $v->description . '</a>';
					$return_arr[$k]['name'] = $v->name;
				}
				// dashboard has a url of /admin
				elseif ($v->name == 'dashboard')
				{
					$return_arr[$k]['link'] = '<a href="' . site_url('admin') . '">' . $v->description . '</a>';
					$return_arr[$k]['name'] = $v->name;
				}
			}

			// otherwise, it's all the same 
			else
			{
				$return_arr[$k]['link'] = '<a href="' . site_url('admin_' . $v->name) . '">' . $v->description . '</a>';
				$return_arr[$k]['name'] = $v->name;
			}
		}
		return $return_arr;
	}

	public function build_perms_links($perms)
	{
		// default empty array
		$return_arr = [];

		// build the links
		foreach($perms as $perm)
		{
			// two odd cases, where I didn't necessarily stick to 
			// the normal convention I set...
			if ($perm->name == 'settings' || $perm->name == 'dashboard')
			{	
				// setting has a url of /admin/settings
				if ($perm->name == 'settings')
				{
					$return_arr[] = '<a class="dropdown-item" href="' . site_url('admin/' . $perm->name) . '">' . $perm->description . '</a>';
				}
				// dashboard has a url of /admin
				elseif ($perm->name == 'dashboard')
				{
					$return_arr[] = '<a class="dropdown-item" href="' . site_url('admin') . '">' . $perm->description . '</a>';
				}
			}
			// otherwise, it's all the same 
			else
			{
				$return_arr[] = '<a class="dropdown-item" href="' . site_url('admin_' . $perm->name) . '">' . $perm->description . '</a>';
			}
		}
		return $return_arr;
	}


	/**
	 * check permissions
	 *
	 * 
	 * @author Enliven Applications
	 * 
	 * @return bool
	 **/
	public function check_perm($perm, $group_id)
	{
		// first get the permmission info
		if ( ! $perm_db = $this->db->where('name', $perm)->limit(1)->get($this->tables['permissions'])->row() )
		{
			return false;
		}

		// now we have all the info we need to 
		// decide if the group has permission
		// to do the thing...
		if ( $this->db->where('group_id', $group_id)->where('perms_id', $perm_db->id)->limit(1)->count_all_results($this->tables['groups_perms']) == 1 )
		{
			return true;
		}
		return false;
	}


		/**
	 * update_perm
	 *
	 * @return bool
	 * @author Enliven Applications
	 **/
	public function update_perm($perm_id = FALSE, $perm_name = FALSE, $additional_data = array())
	{
		if (empty($perm_id)) return FALSE;

		$data = array();

		if (!empty($perm_name))
		{
			// we are changing the name, so do some checks

			// bail if the group name already exists
			$existing_perm = $this->db->get_where($this->tables['permissions'], array('name' => $perm_name))->row();
			if(isset($existing_perm->id) && $existing_perm->id != $perm_id)
			{
				$this->set_error('perm_already_exists');
				return FALSE;
			}

			$data['name'] = $perm_name;
		}


		// IMPORTANT!! Third parameter was string type $description; this following code is to maintain backward compatibility
		// New projects should work with 3rd param as array
		if (is_string($additional_data)) $additional_data = array('description' => $additional_data);


		// filter out any data passed that doesnt have a matching column in the groups table
		// and merge the set group data and the additional data
		if (!empty($additional_data)) $data = array_merge($this->_filter_data($this->tables['permissions'], $additional_data), $data);


		$this->db->update($this->tables['permissions'], $data, array('id' => $perm_id));

		$this->set_message('group_update_successful');

		return TRUE;
	}


}
