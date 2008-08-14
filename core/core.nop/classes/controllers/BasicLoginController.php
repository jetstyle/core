<?php
/**
 *   онтроллер внутренней страницы сайту
 *
 */

Finder::useClass("controllers/Controller");
class BasicLoginController extends Controller
{
	protected $tpl_login_sitemap = 'login';
	protected $tpl_login_form = '_login/form.html';
	protected $tpl_login_form_store_to = '_maincolumn';

	protected $tpl_success_sitemap = 'login';
	protected $tpl_success_form = '_login/user.html';
	protected $tpl_success_form_store_to = '_maincolumn';
	protected $tpl_success_data = '*';

	function handle()
	{
		parent::handle();

		$tpl = &Locator::get('tpl');
		$prp = &Locator::get('principal');
		
		if ($prp->identify(PRINCIPAL_NO_REDIRECT) > PRINCIPAL_AUTH)
		{
			$tpl->parse($this->tpl_login_form,
				$this->tpl_login_form_store_to);
			$this->siteMap = $this->tpl_login_sitemap;
		}
		else
		{
			$tpl->set($this->tpl_success_data, $prp->data);
			$tpl->parse($this->tpl_success_form,
				$this->tpl_success_form_store_to);
			$this->siteMap = $this->tpl_success_sitemap;
		}
	}

}


?>
