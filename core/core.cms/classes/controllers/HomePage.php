<?php
/**
 *  Контроллер главной страницы
 *
 */

Finder::useClass("controllers/BasicPage");
class HomePage extends BasicPage
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
			$this->rh->redirect($this->rh->base_url.'login');
		}
	}
}
?>