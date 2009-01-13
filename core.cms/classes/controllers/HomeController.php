<?php
/**
 *  Контроллер главной страницы
 *
 */
Finder::useClass("controllers/Controller");
class HomeController extends Controller
{
//	protected $plugins = array();
//
//	protected $params_map = array(
//		array('default', array(NULL)),
//	);

	function handle()
	{
		if (Locator::get('principal')->security('noguests'))
		{
			Controller::redirect(RequestInfo::$baseUrl.'start');
		}
		else
		{
			Controller::redirect(RequestInfo::$baseUrl.'login');
		}
	}
}
?>