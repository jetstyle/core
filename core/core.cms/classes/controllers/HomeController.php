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
		if ($this->rh->principal->isAuth())
		{
			$this->rh->redirect(RequestInfo::$baseUrl.'start');
		}
		else
		{
			$this->rh->redirect(RequestInfo::$baseUrl.'login');
		}
	}
}
?>