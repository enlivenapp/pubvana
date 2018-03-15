<?php 

// pubvana/widgets/hello_world/Hello_world.php

// class must extend Widgets
class Pubvana_categories extends Widgets
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

        $this->db->select('id, name, url_name')
                    ->select('(SELECT COUNT(' . $this->db->dbprefix($this->_table['posts_to_categories'] . '.id') . ' ) FROM ' . $this->db->dbprefix($this->_table['posts_to_categories']) . ' WHERE ' . $this->db->dbprefix($this->_table['posts_to_categories'] . '.category_id') . ' = ' . $this->db->dbprefix($this->_table['categories'] . '.id') . ') AS posts_count', FALSE) 
                    ->order_by('id', 'ASC')
                    ->limit($data->numcats);
        
        $query = $this->db->get($this->_table['categories']);
            
        if ($query->num_rows() > 0)
        {           
            $data->categories =  $query->result();

            return $this->render('display', $data);
        }
    }

} 
