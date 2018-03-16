<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends PV_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('Search_m');
		$this->load->library('form_validation');

		$this->form_validation->set_error_delimiters('<div class="alert alert-danger" role="alert">', '</div>');
	}

	
	public function index()
	{

		if ($this->input->post())
		{

			$this->form_validation->set_rules('search_term', lang('search_term'), 'required');
			$this->form_validation->set_rules('search_in', lang('pages') . " &amp; " . lang('posts'), 'required');
			$this->form_validation->set_rules('titlebody', lang('title') . " &amp; " . lang('body'), 'required');


			if ($this->form_validation->run() == TRUE)
            {
                $results = $this->Search_m->search($this->input->post());

                $data['params']	= $this->input->post();
                $data['results'] = $results;  
                $data['page'] = ['title' => lang('search_results_hdr') . "<i> " . $this->input->post('search_term') . "</i>"];

				$this->template->build('search/results', $data); 
            }
            else
            {
            	// get page data
			$data['page'] = ['title' => lang('search_hdr')];

			$this->template->build('search/index', $data);
            }
            
		}
		else
		{
			// get page data
			$data['page'] = ['title' => lang('search_hdr')];

			$this->template->build('search/index', $data);
		}
		
	}



}
