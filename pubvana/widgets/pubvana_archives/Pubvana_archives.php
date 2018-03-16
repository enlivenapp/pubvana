<?php 

// pubvana/widgets/hello_world/Hello_world.php

// class must extend Widgets
class Pubvana_archives extends Widgets
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


        $this->db->select('COUNT(' . $this->db->dbprefix($this->_table['posts'] . '.id') . ') AS posts_count, ' . $this->db->dbprefix($this->_table['posts'] .'.date_posted') . ' FROM ' . $this->db->dbprefix($this->_table['posts']) . ' WHERE ' . $this->db->dbprefix($this->_table['posts'] .'.status') . ' = \'published\' GROUP BY SUBSTRING(' . $this->db->dbprefix($this->_table['posts'] .'.date_posted') . ', 1, 7)', FALSE)
                    ->order_by($this->db->dbprefix($this->_table['posts'] . '.date_posted'), 'DESC')
                    ->limit($data->numarchives);
        
        $query = $this->db->get();
        
        // we can haz results?
        if ($query->num_rows() > 0)
        {
            $result = $query->result();
            
            foreach ($result as &$item)
            {
                $item->url  = date('Y', strtotime($item->date_posted)) . '/' . date('m', strtotime($item->date_posted)) . '/';
                $item->date_posted  = strftime('%B %Y', strtotime($item->date_posted));
            }

            $data->archives = $query->result();

            return $this->render('display', $data);
        }
        return false; 
    }

} 
