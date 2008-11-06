<?php
/**
 *  Начальная страница КМС
 *
 */

Finder::useClass("controllers/Controller");
class StartController extends Controller
{
	protected $plugins = array(
		array('ToolbarPlugin', array(
			'__aspect' => 'Toolbar',
			'store_to' => 'toolbar',
		)),
	);

	protected $params_map = array(
		array('default', array(NULL)),
	);

	public function handle()
	{
		if (!Locator::get('principal')->isAuth())
		{
			Controller::redirect(RequestInfo::$baseUrl.'login');
		}

		parent::handle();
	}

	protected function handle_default($config)
	{
		if(!Locator::get('principal')->isGrantedTo('start') )
		{
			return Controller::deny();
		}

		Finder::useClass('Toolbar');
		$toolbar = new Toolbar();
		Locator::get('tpl')->set('toolbar_main', $toolbar->getMainItems());
		
		$this->siteMap = "start";
	}
}
?>