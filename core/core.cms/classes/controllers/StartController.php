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

	function handle()
	{
		if (!$this->rh->principal->isAuth())
		{
			$this->rh->redirect(RequestInfo::$baseUrl.'login');
		}

		parent::handle();
	}

	function handle_default($config)
	{
		if( !$this->rh->principal->isGrantedTo('start') )
		{
			return $this->rh->deny();
		}

		$this->siteMap = "start";
	}
}
?>