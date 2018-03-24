<?php 


/*

These may end up in the database with
	themes having a details.php file to set
	the front end framework...

 */

/*
	This is the pagination settings for 
	bootstrap 4.x
 */


/*
	$config['full_tag_open']    = '<div class="paging text-center"><nav><ul class="pagination">';
    $config['full_tag_close']   = '</ul></nav></div>';
    $config['num_tag_open']     = '<li class="page-item"><span class="page-link">';
    $config['num_tag_close']    = '</span></li>';
    $config['cur_tag_open']     = '<li class="page-item active"><span class="page-link">';
    $config['cur_tag_close']    = '<span class="sr-only">(current)</span></span></li>';
    $config['next_tag_open']    = '<li class="page-item"><span class="page-link">';
    $config['next_tag_close']  = '<span aria-hidden="true"></span></span></li>';
    $config['prev_tag_open']    = '<li class="page-item"><span class="page-link">';
    $config['prev_tag_close']  = '</span></li>';
    $config['first_tag_open']   = '<li class="page-item"><span class="page-link">';
    $config['first_tag_close'] = '</span></li>';
    $config['last_tag_open']    = '<li class="page-item"><span class="page-link">';
    $config['last_tag_close']  = '</span></li>';
	$config['first_link'] =  false;
	$config['last_link'] = false;
	$config['num_links'] = 5;
*/

/*
	This is the pagination settings for 
	bootstrap 3.x

	Default: comment below if you will
	use semantic UI
 */

$config['full_tag_open'] = '<div class="text-center"><ul class="pagination pagination-small pagination-centered">';
$config['full_tag_close'] = '</ul></div>';
$config['num_links'] = 5;
$config['prev_link'] = '&lt; Prev';
$config['prev_tag_open'] = '<li>';
$config['prev_tag_close'] = '</li>';
$config['next_link'] = 'Next &gt;';
$config['next_tag_open'] = '<li>';
$config['next_tag_close'] = '</li>';
$config['cur_tag_open'] = '<li class="active"><a href="#">';
$config['cur_tag_close'] = '</a></li>';
$config['num_tag_open'] = '<li>';
$config['num_tag_close'] = '</li>';
$config['first_link'] = FALSE;
$config['last_link'] = FALSE;
$config['anchor_class'] = 'follow_link';



/*
	This is the pagination settings for 
	Semantic UI

	Uncomment below to use.

$config['full_tag_open'] = '<div class="ui pagination menu">';
$config['full_tag_close'] ='</div>';
$config['num_tag_open'] = '<li class="item">';
$config['num_tag_close'] = '</li>';
$config['cur_tag_open'] = '<li class="active item">';
$config['cur_tag_close'] = '</li>';
$config['next_tag_open'] = '<li class="item">';
$config['next_tagl_close'] = '</li>';
$config['prev_tag_open'] = '<li class="item">';
$config['prev_tagl_close'] = '</li>';
$config['first_tag_open'] = '<li class="item">';
$config['first_tagl_close'] = '</li>';
$config['last_tag_open'] = '<li class="item">';
$config['last_tagl_close'] = '</li>';

 */
