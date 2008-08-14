<?php
/**
 *  Контроллер главной страницы
 *
 */
Finder::useClass("controllers/Controller");
class HomeController extends Controller
{
	protected $plugins = array();

	protected $params_map = array(
		array('default', array(NULL)),
	);

	function handle_default($config)
	{
		if (Locator::get('principal')->isAuth())
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