<?php
/**
 *  Контроллер модулей
 *
 */

$this->UseClass("controllers/BasicPage");
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
		//первый конфиг
		$this->rh->useClass("ModuleConstructor");
		$moduleConstructor =& new ModuleConstructor($this->rh);
		$moduleConstructor->initialize($config['module']);
		$this->rh->tpl->set('module_body', $moduleConstructor->proceed($config['mode']));


		/*
		$moduleConfig->read("defs");
		if ($config['mode'])
		{
			$moduleConfig->read( $config['mode'] );
		}

		//основной модуль
		$module = $moduleConfig->initModule();
		$module->store_to = "module_body";
		$module->handle();
		*/
		$this->rh->site_map_path = 'module';
	}
}
?>