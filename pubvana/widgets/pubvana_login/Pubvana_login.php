<?php 

// pubvana/widgets/hello_world/Hello_world.php

// class must extend Widgets
class Pubvana_login extends Widgets
{


	// must construct parent
	public function __construct()
	{
		parent::__construct();
	}



	// we call this method to get things started
    public function run($data) {

        // instance of Ion_auth
        $Ion_auth = new Ion_auth;

        // if the user is already logged in,
        // we want the login form to go away and
        // not bother them...
        if (! $Ion_auth->logged_in() )
        {
            // not logged in, render the form.
            return $this->render('display', $data);
        }
        else
        {
            // they're logged in, cancel.
            
            // a special condition.  This gives
            // us the change to anagrammatically 
            // nuke the display at runtime. 
            // simply return String 'cancel'
            return 'cancel';
        }  
    }
} 
