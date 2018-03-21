<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Search M
 * 
 * Public Search Model Class
 *
 * @access  public
 * @author  Enliven Applications
 * @version 3.0
 * 
*/
class Search_m extends CI_Model
{
	// Protected or private properties
	protected $_table;
	
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

		$tables = $this->config->item('pubvana');
		$this->_table = $tables['tables'];
	}

	public function search($formPost)
	{
		//print_r($formPost);

		// if we're only searching for a post...
		if ($formPost['search_in'] == 'posts')
		{
			return $this->search_posts($formPost);
		}
		elseif ($formPost['search_in'] == 'pages')
		{
			return $this->search_pages($formPost);
		}

	}




		/**
     * search_posts
     * 
     * searches blog posts
     *
     * @access  public
     * @author  Enliven Applications
     * @version 3.0
     * 
     * @return  array
     */
	public function search_posts($formPost)
	{
		// today's date
		$current_date = date('Y-m-d');
		
		if ($formPost['titlebody'] == 'both')
		{
			$this->db->
					like('title', $formPost['search_term'])
					->where('status', 'published')
					->where('date_posted <= ', $current_date)
					->or_like('content', $formPost['search_term'])
					->where('status', 'published')
					->where('date_posted <= ', $current_date);
		}
		elseif ($formPost['titlebody'] == 'title')
		{
			$this->db
					->where('status', 'published')
					->where('date_posted <= ', $current_date)
					->like('title', $formPost['search_term']);
		}
		elseif ($formPost['titlebody'] == 'body')
		{
			$this->db
					->like('content', $formPost['search_term'])
					->where('status', 'published')
					->where('date_posted <= ', $current_date);
		}

		$this->db
				->order_by('featured', 'DESC')
				->order_by('sticky', 'DESC')
				->order_by('id', 'DESC')
				->limit($this->config->item('posts_per_page'));
			
		$results = $this->db->get($this->_table['posts'])->result();

		if ($results)
		{
			$this->load->library('markdown');

			foreach ($results as &$result)
			{
				$result->date_display = date('M, t Y',strtotime($result->date_posted));
				$result->link = post_url($result->url_title);

				// parse markdown & strip tags
				$result->content = strip_tags($this->markdown->parse($result->content));

				if (strlen($result->content) > 100)
				{
					$result->content = substr($result->content, 0, 100) . '...';
				}

			}
		}
		
		return $results;	
	}




	public function search_pages($formPost)
	{
		if ($formPost['titlebody'] == 'both')
		{
			$results = $this->db
					->like('title', $formPost['search_term'])
					->where('status', 'active')
					->or_like('content', $formPost['search_term'])
					->where('status', 'active')
					->limit($this->config->item('posts_per_page'))
					->get($this->_table['pages'])
					->result();
		}
		elseif ($formPost['titlebody'] == 'title')
		{
			$results = $this->db
					->like('title', $formPost['search_term'])
					->where('status', 'active')
					->limit($this->config->item('posts_per_page'))
					->get($this->_table['pages'])
					->result();
		}
		elseif ($formPost['titlebody'] == 'body')
		{
			$results = $this->db
					->like('content', $formPost['search_term'])
					->where('status', 'active')
					->limit($this->config->item('posts_per_page'))
					->get($this->_table['pages'])
					->result();
		}
		else
		{
			// fuckery is afoot
			return false;
		}

		$this->load->library('markdown');

		foreach ($results as &$result)
		{
			$result->link = page_url($result->url_title);
			$result->date_display = date('M, t Y',strtotime($result->date));

			// parse markdown & strip tags
			$result->content = strip_tags($this->markdown->parse($result->content));

			// we don't need the whole page content, so we'll limit it to <100 characters
			if (strlen($result->content) > 100)
			{
				$result->content = substr($result->content, 0, 100) . '...';
			}
		}

		return $results;
	}


}
