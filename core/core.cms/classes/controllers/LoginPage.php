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
			if ($this->rh->ri->get('logout'))
			{
				$this->rh->principal->logout($this->rh->base_url.'login');
			}

			if ($_GET['retpath']) {            	$this->rh->redirect($_GET['retpath']);
			} else {
				$this->rh->redirect($this->rh->base_url.'start');
			}
		}
		else
		{
			$this->rh->site_map_path = 'login';
		}
	}
}
?>