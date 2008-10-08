<?php
/**
 *  Module packer controller.
 * 
 *  @author lunatic <lunatic@jetstyle.ru>
 */

Finder::useClass("controllers/Controller");
class ModulePackerController extends Controller
{
	protected $plugins = array(
		array('ToolbarPlugin', array(
			'__aspect' => 'Toolbar',
			'store_to' => 'toolbar',
		)),
	);

	protected $params_map = array(
		array('default', array(
			'module' => '[\w\-]+',
		)),
		array('default', array(NULL)),
	);

	public function handle()
	{
		if (!Locator::get('principal')->isAuth())
		{
			Controller::redirect(RequestInfo::$baseUrl.'login');
		}

		parent::handle();
	}

	public function handle_default($config)
	{
		// force UTF8
		Locator::get('db')->query("SET NAMES utf8");

		Finder::useClass("ModulePacker");
		$modulePacker =& new ModulePacker();
		$modulePacker->pack($config['module']);

		$this->siteMap = 'module';
	}
}
?>