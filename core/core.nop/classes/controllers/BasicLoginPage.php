<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

$this->UseClass("controllers/BasicPage");
class BasicLoginPage extends BasicPage
{

	function Handle()
	{

		parent::handle();
		#$this->rh->tpl->set('*', $this->rh->data);
		if ($this->rh->principal->Identify(PRINCIPAL_NO_REDIRECT) > PRINCIPAL_AUTH)
		{
			$this->rh->tpl->parse('_login/form.html', '_maincolumn');
			$this->rh->site_map_path = 'login';
		}
		else
		{
			//var_dump($this->rh->principal->Security('role', 'user'));
			$this->rh->tpl->set('*', $this->rh->principal->data);
			$this->rh->tpl->parse('_login/user.html', '_maincolumn');
			$this->rh->site_map_path = 'login';
		}
	}

}	


?>
