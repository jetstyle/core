<?php
Finder::useClass("controllers/BasicPage");
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
				$redirectTo = $this->rh->ri->get('retpath') ?
							  $this->rh->ri->get('retpath') :
							  $this->rh->base_url.'login';
				$this->rh->principal->logout($redirectTo);
			} else {
				if ($this->rh->ri->get('retpath')) {	            	$this->rh->redirect($this->rh->ri->get('retpath'));
				} else {
					$this->rh->redirect($this->rh->base_url.'start');
				}
			}
		}
		else
		{
			$this->rh->site_map_path = 'login';
		}
	}
}
?>