<?php
/**
 *  Контроллер главной страницы сайту
 *
 */

Finder::useClass("controllers/Controller");
class UserSessionsCleanController extends Controller
{
	function handle() 
	{
     	Locator::get('principal')->getSessionModel()->cleanup();
     	die();
	}
}
?>