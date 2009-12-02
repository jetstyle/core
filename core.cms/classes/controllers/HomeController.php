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

			Controller::redirect(RequestInfo::$baseUrl.Router::linkTo('Start'));
		}
		else
		{
			Controller::redirect(RequestInfo::$baseUrl.Router::linkTo('Users::login'));
		}
	}
}
?>