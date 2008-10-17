<?php
/**
 *  Контроллер модулей
 *
 */

Finder::useClass("controllers/Controller");
class DoController extends Controller
{
 	protected $plugins = array(
		array('ToolbarPlugin', array(
			'__aspect' => 'Toolbar',
			'store_to' => 'toolbar',
		)),
	);

	protected $params_map = array(
		array('default', array(
			'module' => '\w+',
			'mode' => '\w+',
		)),
		array('default', array(
			'module' => '\w+',
		)),
		array('start', array(NULL)),
	);

	function handle()
	{
		if (!Locator::get('principal')->isAuth())
		{
			$redirectTo = RequestInfo::$baseUrl.'login'; //login page
            $redirectTo .= '?retpath='; //path to return there
            $retPath .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $retPath .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            $retPath = urlencode($retPath);
			Controller::redirect($redirectTo.$retPath);
		}

		parent::handle();
	}

	function handle_start($config)
	{
		Controller::redirect(RequestInfo::$baseUrl.'start');
	}

	function handle_default($config)
	{
		$params = $this->params;
//		if ($config['mode'])
//		{
//			unset($params[0], $params[1]);
//		}
//		else
//		{
			unset($params[0]);
//		}

		Finder::useClass("ModuleConstructor");
		$moduleConstructor =& new ModuleConstructor();
		$moduleConstructor->initialize($config['module'], $params);

		Locator::get('tpl')->set('module_body', $moduleConstructor->proceed());

		$this->data['title_short'] = $moduleConstructor->getTitle();
		$this->siteMap = 'module';
	}

	public function url_to($cls=NULL, $item=NULL)
	{
		$result = '';
		$cls = strtolower($cls);

		switch($cls)
		{
			case 'module':
				$result = $this->path.'/'.$item['href'];
			break;
		}

		if (strlen($result) > 0)
		{
			return $result;
		}
		else
		{
			return parent::url_to($cls, $item);
		}
	}
}
?>