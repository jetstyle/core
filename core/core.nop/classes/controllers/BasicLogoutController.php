<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

Finder::useClass("controllers/Controller");
class BasicLogoutController extends Controller
{
	function handle()
	{
		$prp = &Locator::get('principal');
		$prp->Logout(PRINCIPAL_REDIRECT, RequestInfo::$baseUrl);
		parent::handle();
	}
}


?>
