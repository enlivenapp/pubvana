<?php 
// class must extend Widgets
class Pubvana_popular_posts extends Widgets
{

	// must construct parent
	public function __construct()
	{
		parent::__construct();

        $tables = $this->config->item('pubvana');
        $this->_table = $tables['tables'];

	}



	// we call this method to get things started
    public function run($data) {

    	$query = $this->db->where('status', 'published')->limit($data->numposts)->order_by('post_count', 'DESC')->get($this->_table['posts']);
            
        if ($query->num_rows() > 0)
        {
            $data->posts = $query->result();

            return $this->render('display', $data);
        }

        
    }

} 
