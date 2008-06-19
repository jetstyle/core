<?php
/**
 *  ”паковщик модулей
 *
 */

$this->UseClass("controllers/BasicPage");
class ModulePackerPage extends BasicPage
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

	public function handle()
	{
		if (!$this->rh->principal->isAuth())
		{
			$this->rh->redirect($this->rh->base_url.'login');
		}

		parent::handle();
	}

	public function handle_default($config)
	{		
		$this->rh->useClass("ModulePacker");
		$modulePacker =& new ModulePacker($this->rh);
		$modulePacker->pack();
		
		$this->rh->site_map_path = 'module';
	}
}
?>