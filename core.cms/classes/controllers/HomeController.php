<?php
/**
 *  Контроллер главной страницы
 *
 */
Finder::useClass("controllers/Controller");
class HomeController extends Controller
{
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