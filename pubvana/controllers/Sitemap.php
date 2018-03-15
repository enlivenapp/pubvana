<?php  defined('BASEPATH') OR exit('No direct script access allowed');

Class Sitemap extends PV_Controller {


		public function __construct()
	{
		parent::__construct();

		// load up Pages and Posts models maybe?
		$this->load->model('Sitemap_m');

	}



    public function index()
    {

        $data['data'] = $this->Sitemap_m->getSitemapUrls();

        //print_r($data);
        header("Content-Type: text/xml;charset=iso-8859-1");
        $this->load->view("sitemap/index", $data);
    }


    public function robots()
    {
    	header("Content-Type: text/plain");
    	$this->load->view("sitemap/robots");
    }
}
