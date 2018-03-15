<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Contact M
 * 
 * Public Contact Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class Contact_m extends CI_Model
{

	public function insert_contact()
	{
		$data = [
        'name' 			=> $this->input->post('Name'),
        'email' 		=> $this->input->post('email'),
        'message'		=> $this->input->post('Message'),
        'sender_ip'		=> $this->input->ip_address()
		];

		if ($this->db->insert('contacts', $data))
		{
			return true;
		}
		else
		{
			return false;
		}
	}



}
