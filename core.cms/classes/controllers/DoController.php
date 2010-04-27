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
                if ($_GET["hide_toolbar"])
                    Locator::get("tpl")->set("hide_toolbar", 1);
        
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

		Finder::useClass("ModuleConstructor");
                $modulePath = $config['module'].( $params ? '/'.implode('/', $params) : '' );

                if ((!defined('COMMAND_LINE') || !COMMAND_LINE) && !Locator::get('principal')->security('cmsModules', $modulePath))
		{
			return Controller::deny();
		}

		$this->moduleConstructor = ModuleConstructor::factory($modulePath);

                Locator::get('tpl')->set('module_name', $modulePath);
                Locator::get('tpl')->set('module_title', $this->moduleConstructor->getTitle());
		Locator::get('tpl')->set('module_body', $this->moduleConstructor->getHtml());

               
		$this->data['title_short'] = $this->moduleConstructor->getTitle();

		$this->siteMap = 'module';
	}

        function breadcrumbsWillRender($block)
        {
                $elements = Locator::getBlock("menu")->getParentNodes();

                foreach ( $elements as $el )
                {
                    Locator::getBlock("breadcrumbs")->addItem( $el["path"], $el["title"] );

                }
                Locator::getBlock("breadcrumbs")->addItem( $this->moduleConstructor->getPath(), $this->moduleConstructor->getTitle() );
        }

	function handle_pack_modules($config)
	{
		// force UTF8
		Locator::get('db')->query("SET NAMES utf8");

		Finder::useClass("ModulePacker");
		$modulePacker = new ModulePacker();
		$modulePacker->pack();

		die('0');
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
