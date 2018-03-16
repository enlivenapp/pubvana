<?php 

// pubvana/widgets/hello_world/Hello_world.php

// class must extend Widgets
class Html extends Widgets
{

	// must construct parent
	public function __construct()
	{
		parent::__construct();
	}



	// we call this method to get things started
    public function run($data) {

        return $this->render('display', $data);
    }

} 
