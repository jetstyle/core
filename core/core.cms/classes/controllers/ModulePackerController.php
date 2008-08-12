<?php
/**
 *  ”паковщик модулей
 *
 */

Finder::useClass("controllers/BasicPage");
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
		// force UTF8
		$this->rh->db->query("SET NAMES utf8");

		Finder::useClass("ModulePacker");
		$modulePacker =& new ModulePacker($this->rh);
		$modulePacker->pack();

		$this->rh->site_map_path = 'module';
	}
}
?>