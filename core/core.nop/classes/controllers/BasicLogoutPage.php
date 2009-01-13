<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

$this->UseClass("controllers/BasicPage");
class BasicLogoutPage extends BasicPage
{
	function Handle()
	{
		$this->rh->principal->Logout(PRINCIPAL_REDIRECT, $this->rh->base_url);
		parent::handle();
	}
}	


?>
