<?php
/**
 *  Module packer controller.
 * 
 *  @author lunatic <lunatic@jetstyle.ru>
 */

Finder::useClass("controllers/Controller");
class ModulePackerController extends Controller
{
	protected $params_map = array(
		array('default', array(
			'module' => '[\w\-]+',
		)),
		array('default', array(NULL)),
	);

	public function handle()
	{
		if (!Locator::get('principal')->security('god'))
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

		Locator::get('db')->query("SET NAMES ".Config::get('db_set_encoding'));
		
		$this->siteMap = 'module';
	}
}
?>