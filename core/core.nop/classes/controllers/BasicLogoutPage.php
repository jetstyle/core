<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

Finder::useClass("controllers/BasicPage");
class BasicLogoutPage extends BasicPage
{
	function Handle()
	{
		$this->rh->principal->Logout(PRINCIPAL_REDIRECT, $this->rh->base_url);
		parent::handle();
	}
}


?>
