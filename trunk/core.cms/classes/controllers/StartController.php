<?php
/**
 *  Начальная страница КМС
 *
 */

Finder::useClass("controllers/Controller");
class StartController extends Controller
{
	protected $params_map = array(
		array('default', array(NULL)),
	);

	public function handle()
	{
		if (!Locator::get('principal')->security('noguests'))
		{
			Controller::redirect(RequestInfo::$baseUrl.'login');
		}

		parent::handle();
	}

	protected function handle_default($config)
	{
		Finder::useClass('Toolbar');
		$toolbar = new Toolbar();
		Locator::get('tpl')->set('toolbar_main', $toolbar->getMainItems());
		
		$this->siteMap = "start";
	}
}
?>