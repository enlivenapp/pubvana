<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_media extends PV_AdminController {


    public function __construct()
    {
        parent::__construct();

        // does the user have permission to 
        // view/use this method?
        if ( ! $this->pv_auth->has_permission('widgets'))
        {
            // nope, boot'm out
            $this->session->set_flashdata('error', lang('permission_check_failed'));
            redirect();
        }

        // set active_link so we know what to 
        // set class="active" to in the nav menu
        $this->template->set('active_link', 'media');

        //$this->template->append_css('theme-bootstrap-libreicons-svg.css');
        $this->template->append_css('default.css');
    }



    public function index()
    {

        $this->load->helper('url');
        $data['connector'] = site_url('admin_media/connector');

        //// PITA ////
        $data['full_version'] = '2.1.34';

        //$this->load->view('admin/media/elfinder', $data);
        $this->template->build('admin/media/elfinder', $data);
    }
   
    public function connector()
    {
        $this->load->helper('url');
        $opts = array(
            'roots' => array(
                array( 
                    'driver'        => 'LocalFileSystem',
                    'path'          => FCPATH . '/uploads',
                    'URL'           => base_url('uploads'),
                    'uploadDeny'    => array('all'),                  // All Mimetypes not allowed to upload
                    'uploadAllow'   => array('image', 'text/plain', 'application/pdf', 'application/zip'),// Mimetype `image` and `text/plain` allowed to upload
                    'uploadOrder'   => array('deny', 'allow'),        // allowed Mimetype `image` and `text/plain` only
                    'accessControl' => array($this, 'elfinderAccess'),// disable and hide dot starting files (OPTIONAL)
                    // more elFinder options here
                ) 
            ),
        );
        $connector = new elFinderConnector(new elFinder($opts));
        $connector->run();
    }
    
    public function elfinderAccess($attr, $path, $data, $volume, $isDir, $relpath)
    {
        $basename = basename($path);
        return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
                 && strlen($relpath) !== 1           // but with out volume root
            ? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
            :  null;                                 // else elFinder decide it itself
    }
}
