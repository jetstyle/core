<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

$this->UseClass("controllers/BasicPage");
class BasicLoginPage extends BasicPage
{
	var $tpl_login_sitemap = 'login';
	var $tpl_login_form = '_login/form.html';
	var $tpl_login_form_store_to = '_maincolumn';

	var $tpl_success_sitemap = 'login';
	var $tpl_success_form = '_login/user.html';
	var $tpl_success_form_store_to = '_maincolumn';
	var $tpl_success_data = '*';

	function Handle()
	{

		parent::handle();
		#$this->rh->tpl->set('*', $this->rh->data);
		if ($this->rh->principal->Identify(PRINCIPAL_NO_REDIRECT) > PRINCIPAL_AUTH)
		{
			$this->rh->tpl->parse($this->tpl_login_form, 
				$this->tpl_login_form_store_to);
			$this->rh->site_map_path = $this->tpl_login_sitemap;
		}
		else
		{
			//var_dump($this->rh->principal->Security('role', 'user'));
			$this->rh->tpl->set($this->tpl_success_data, $this->rh->principal->data);
			$this->rh->tpl->parse($this->tpl_success_form, 
				$this->tpl_success_form_store_to);	
			$this->rh->site_map_path = $this->tpl_success_sitemap;
		}
	}

}	


?>
