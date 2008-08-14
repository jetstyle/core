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
            $redirectTo .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $redirectTo .= $_SERVER['SERVER_NAME'].RequestInfo::hrefChange('');
			Controller::redirect($redirectTo);
		}

		parent::handle();
	}

	function handle_start($config)
	{
		Controller::redirect(RequestInfo::$baseUrl.'start');
	}

	function handle_default($config)
	{
		Finder::useClass("ModuleConstructor");
		$moduleConstructor =& new ModuleConstructor();
		$moduleConstructor->initialize($config['module']);
		Locator::get('tpl')->set('module_body', $moduleConstructor->proceed($config['mode']));

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