<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sitemap M
 * 
 * Public Sitemap Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class Sitemap_m extends CI_Model
{
	
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

		// load up Pages and Posts models maybe?

	}


	public function getSitemapUrls()
	{
		// get pages
		$pages = $this->db->select('url_title, date, date_modified')->where('status', 'active')->get('pages')->result();

		if ($pages)
		{
			foreach ($pages as &$page)
			{
				$page->url = page_url($page->url_title);
			}
		}


		// get posts
		$posts = $this->db->select('url_title, date_posted, date_modified')->where('status', 'published')->get('posts')->result();

		if ($posts)
		{
			foreach ($posts as &$post)
			{
				$post->url 	= post_url($post->url_title);
				$post->date = $post->date_posted; 
			}
		}

		return array_merge($posts, $pages);
	}


}
