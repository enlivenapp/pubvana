<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Widgets
 * 
 * Admin Widgets Controller Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 1.0
 * 
*/
class Admin_widgets extends PV_AdminController {

	/**
     * Construct
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
     * @return  null
     */
	public function __construct()
	{
		parent::__construct();

		// add things we use in views
		$this->template->append_css('default.css');
		$this->template->append_css('ie10-viewport-bug-workaround.css');
		$this->template->append_js('ie10-viewport-bug-workaround.js');

		// Admin model
		$this->load->model('Admin_widgets_m');

		// form helper
		$this->load->helper('form');

		// form validation
		$this->load->library('form_validation');

		// Ion_auth
		$this->load->language('ion_auth');

		// set form validation error default
		$this->form_validation->set_error_delimiters('<div class="alert alert-danger" role="alert">', '</div>');
		
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
		$this->template->set('active_link', 'widgets');
	}

	/**
     * Index
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
     * @return  null
     */
	public function index()
	{
		// load JS for fancy drag and drop
		$this->template->append_css('jquery-ui.min.css');
		$this->template->append_css('jquery-ui.structure.min.css');
		$this->template->append_js('jquery-ui.min.js');


		// get social links
		$data['widgets'] = $this->Admin_widgets_m->get_widgets();
		$data['widgets_list']	= $this->Admin_widgets_m->get_widgets_list();

		// build it and they will come.
		$this->template->build('admin/widgets/index', $data);
	}

	/**
     * Add
     *
     * Adds a widget instance to a particular widget area
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
     * @param $widgetId The ID of the widget from widgets table 
     * @param $areaId The ID of the area the instance is being added
     * @return  null
     */
	public function add($widgetId = null, $areaId = null)
	{
		if ($this->Admin_widgets_m->add_widget_to_area($widgetId, $areaId))
		{
			$this->session->set_flashdata('success', lang('widget_admin_added'));
			redirect('admin_widgets');
		}
		else
		{
			$this->session->set_flashdata('error', lang('widget_admin_added_error'));
			redirect('admin_widgets');
		}
		

	}

	/**
     * Update Instance Order
     *
     * AJAX call to update the order in which the widget appears 
     * on public pages 
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $_POST data
	 *
     * @return  json
     */
	public function update_instance_order()
	{
		if ($result = $this->Admin_widgets_m->update_instance_order($this->input->post()))
		{
			echo json_encode(['msg' => 'success']);
		}
		else
		{
			echo json_encode(['msg' => 'failed']);
		}
	}

	/**
     * Update Instance
     *
     * Admin edit of options and settings for widget
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $_POST data
	 *
     * @return  null
     */
	public function update_instance()
	{

		if ($this->Admin_widgets_m->admin_update_instance($this->input->post()))
		{
			$this->session->set_flashdata('success', lang('widget_admin_update'));
			redirect('admin_widgets');
		}

		$this->session->set_flashdata('error', lang('widget_admin_update_error'));
			redirect('admin_widgets');

	}

	/**
     * Remove Instance
     *
     * Admin - removes widget instance from widget area
     *
     * @access  public
     * @author  Enliven Applications
     * @version 1.0
     * 
	 * @param $instanceId
	 *
     * @return  json
     */
	public function remove_instance($instanceId)
	{
		if ($this->Admin_widgets_m->remove_instance($instanceId))
		{
			$this->session->set_flashdata('success', lang('widget_admin_removed'));
			redirect('admin_widgets');
		}
		$this->session->set_flashdata('error', lang('widget_admin_removed_error'));
			redirect('admin_widgets');
	}




}  // EOC
