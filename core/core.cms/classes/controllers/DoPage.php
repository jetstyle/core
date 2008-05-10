<?php
/**
 *  ���������� �������
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
			$this->rh->redirect($this->rh->base_url.'login');
		}

		parent::handle();
	}

	function handle_start($config)
	{
		$this->rh->redirect($this->rh->base_url.'start');
	}
	
	function handle_default($config)
	{
		//������ ������
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
		
		//�������� ������
		$module = $moduleConfig->initModule();
		$module->store_to = "module_body";
		$module->handle();
		*/
		$this->rh->site_map_path = 'module';
	}
}
?>