<?php 

// pubvana/widgets/hello_world/Hello_world.php

// class must extend Widgets
class Pubvana_links extends Widgets
{

	// must construct parent
	public function __construct()
	{
		parent::__construct();
	}



	// we call this method to get things started
    public function run($data) {

    	$query = $this->db->where('visible', 'yes')->limit($data->numlinks)->order_by('position', 'ASC')->get('links');
            
        if ($query->num_rows() > 0)
        {
            $data->links = $query->result();

            return $this->render('display', $data);
        }

        
    }

} 
