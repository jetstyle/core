<?php
$this->UseClass("controllers/BasicPage");
class LoginPage extends BasicPage
{
	var $plugins = array();
	
	var $params_map = array(
		array('default', array(NULL)),
	);
	
	function handle_default($config)
	{
		if ($this->rh->principal->isAuth())
		{
			$this->rh->redirect($this->rh->base_url.'start');
		}
		else
		{
			if ($this->rh->ri->get('logout')) 
			{
				$this->rh->principal->logout($this->rh->base_url.'login');
			}
			
			$this->rh->site_map_path = 'login';
		}
	}
}
?>