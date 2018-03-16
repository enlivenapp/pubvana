<?php 
// class must extend Widgets
class Pubvana_featured_post extends Widgets
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
          
        if ($data->post = $this->db->where('featured', '1')->limit(1)->get($this->_table['posts'])->row())
        {
            return $this->render('display', $data);
        }

        
    }

} 
