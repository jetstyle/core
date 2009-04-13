<?php
/**
 *  Контроллер модулей
 *
 */

Finder::useClass("controllers/Controller");
class DoController extends Controller
{
	protected $params_map = array(
		array('default', array(
			'module' => '\w+',
			'mode' => '\w+',
		)),
		array('pack_modules', array(
			'pack_modules' => 'pack_modules',
		)),
		array('default', array(
			'module' => '\w+',
		)),
		array('start', array(NULL)),
	);

	function handle()
	{
		if ((!defined('COMMAND_LINE') || !COMMAND_LINE) && !Locator::get('principal')->security('noguests'))
		{
			Controller::deny();
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
		unset($params[0]);
		
		$current = Locator::get('db')->queryOne("SELECT title FROM ??toolbar WHERE href=".Locator::get('db')->quote( $config['module'] ) );
		Locator::get('tpl')->set('module_title', $current['title']);
		
		Finder::useClass("ModuleConstructor");
		$moduleConstructor =& new ModuleConstructor();
		$moduleConstructor->initialize($config['module'], $params);

		Locator::get('tpl')->set('module_body', $moduleConstructor->proceed());

		$this->data['title_short'] = $moduleConstructor->getTitle();
		$this->siteMap = 'module';
	}
	
	function handle_pack_modules($config)
	{
		// force UTF8
		Locator::get('db')->query("SET NAMES utf8");

		Finder::useClass("ModulePacker");
		$modulePacker =& new ModulePacker();
		$modulePacker->pack();
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