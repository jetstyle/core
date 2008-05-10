<?php
/**
 *  Начальная страница КМС
 *
 */

$this->UseClass("controllers/BasicPage");
class StartPage extends BasicPage
{
	var $plugins = array(
		array('ToolbarPlugin', array(
			'__aspect' => 'Toolbar',
			'store_to' => 'toolbar', 
		)),
	);
	
	var $params_map = array(
		array('default', array(NULL)),
	);

	function handle()
	{
		if (!$this->rh->principal->isAuth())
		{
			$this->rh->redirect($this->rh->base_url.'login');
		}

		parent::handle();
	}

	function handle_default($config)
	{
		if( !$this->rh->principal->isGrantedTo('start') )
		{
			return $this->rh->deny();
		}
		
		$this->rh->site_map_path = "start";
	}
}
?>