<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Contact extends PV_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('Contact_m');
		$this->load->library('form_validation');

		$this->form_validation->set_error_delimiters('<div class="alert alert-danger" role="alert">', '</div>');
	}


	public function index()
	{
		if ($this->input->post())
		{
		
			// do we use reCaptcha
			if ($this->config->item('use_recaptcha') == 1)
			{
				$this->form_validation->set_rules('g-recaptcha-response', 'lang:recaptcha', 'callback_verify_recaptcha');
			}

			// are we using the honeypot?
			if ($this->config->item('use_honeypot') == 1)
			{
				if (!empty($this->input->post('date_stamp_gotcha')))
				{
					redirect();
				}
			}


			$this->form_validation->set_rules('Name', lang('contact_name'), 'required');
			$this->form_validation->set_rules('email', lang('contact_email'), 'required|valid_email');
			$this->form_validation->set_rules('Message', lang('contact_msg'), 'required');


			if ($this->form_validation->run() == TRUE)
	        {
	            $results = $this->Contact_m->insert_contact($this->input->post());

	            $data['params']	= $this->input->post();  
	            $data['page'] = ['title' => lang('contact_hdr')];

	            if ($results)
	            {
	            	$message = lang('contact_pt1') . $this->input->post('Name') . lang('contact_pt2') . $this->input->post('email') . lang('contact_pt3') . $this->input->post('Message') . lang('contact_pt4');

	            	$this->pvcore->send_email($this->config->item('admin_email'), lang('contact_subject') . ' - ' . $this->config->item('site_name'),  $message);


					// woot!  set the success message
					$this->session->set_flashdata('success', lang('contact_sent'));

					redirect('contact/success');
	            }
	            else
	            {
	            	$this->session->set_flashdata('error', lang('contact_prob'));
	            	redirect('contact/success');
	            }       
	        }
	        else
	        {
	        	// get page data
				$data['page'] = ['title' => lang('contact_hdr')];

				$this->template->build('contact/index', $data);
	        }
	        
		}
		else
		{
			// get page data
			$data['page'] = ['title' => lang('contact_hdr')];

			$this->template->build('contact/index', $data);
		}
	}




	public function success()
	{
		// get page data
			$data['page'] = ['title' => lang('contact_hdr')];

			$this->template->build('contact/success', $data);
	}




	/**
     * verify reCaptcha
     * 
     * uses Phil Sturgeon's Rest client 
     * to connect to google.com new v2
     * recaptcha system and verify the 
     * captcha token provided by the user
     * is valid.  
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     */
	public function verify_recaptcha($str)
	{
		// applications/libraries
		$this->load->library('rest');
		
		// rest config
		$config = array(
				'server' => 'https://www.google.com/recaptcha/api/',
            );

		// post info to send to google
		$post = array(
				'secret'	=> $this->config->item('recaptcha_private_key'), // see admin settings
				'response'	=> $str, // ridiculously long string from form.
				'remoteip'	=> $this->input->ip_address()  // optional, but we're going to do it anyway.
			);

		// Run Rest initialization
		$this->rest->initialize($config);

		// Pull in response
		$recaptcha = $this->rest->post('siteverify', $post);

		// because dashes in objects... 
		// bleh.  Thanks google.
		$recaptcha = (array) $recaptcha;
		
		// errors?
		if ( isset($recaptcha['error-codes']))
		{	
			// we'll need humanize() shortly.
			$this->load->helper('inflector');
			
			// add errors to the form_validation error message
			foreach ($recaptcha['error-codes'] as $error)
			{
				/*
				Set a human readable error message.

				Fun fact: an undocumented second param in humanize() allows
						  one to specify the Input Separator.  the default is
						  the underscore.  Google returns a dash.
				 */
				$this->form_validation->set_message('verify_recaptcha', 'Recaptcha - ' . humanize($error, '-'));
			}
			// there were errors, so the callback fails
			return false;
		}
		// no errors.  Winner, winner, chicken dinner.
		return true;
	}


}
