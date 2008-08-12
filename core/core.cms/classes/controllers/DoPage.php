<?php
/**
 *  Контроллер модулей
 *
 */

Finder::useClass("controllers/BasicPage");
class DoPage extends BasicPage
{
	var $plugins = array(
		array('ToolbarPlugin', array(
			'__aspect' => 'Toolbar',
			'store_to' => 'toolbar',
		)),
	);

	var $params_map = array(
		array('default', array(
			'module' => '^\w+$',
			'mode' => '^\w+$',
		)),
		array('default', array(
			'module' => '^\w+$',
		)),
		array('start', array(NULL)),
	);

	function handle()
	{
		if (!$this->rh->principal->isAuth())
		{
			$redirectTo = $this->rh->base_url.'login'; //login page
            $redirectTo .= '?retpath='; //path to return there
            $redirectTo .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $redirectTo .= $_SERVER['SERVER_NAME'].$this->rh->ri->hrefPlus('');
			$this->rh->redirect($redirectTo);
		}

		parent::handle();
	}

	function handle_start($config)
	{
		$this->rh->redirect($this->rh->base_url.'start');
	}

	function handle_default($config)
	{
		Finder::useClass("ModuleConstructor");
		$moduleConstructor =& new ModuleConstructor($this->rh);
		$moduleConstructor->initialize($config['module']);
		$this->rh->tpl->set('module_body', $moduleConstructor->proceed($config['mode']));

		$this->config['title_short'] = $moduleConstructor->getTitle();
		$this->rh->site_map_path = 'module';
	}

	public function url_to($cls=NULL, $item=NULL)
	{
		$result = '';
		$cls = strtolower($cls);

		switch($cls)
		{
			case 'module':
				$result = 'do/'.$item['href'];
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