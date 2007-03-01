<?php

class Plugin 
{
	/** Имена параметров конфигурации плагина */
	var $config_vars = array();
	var $inialized = False;

	function Plugin(&$factory, $config)
	{
		$this->rh =& $factory->rh;
		$this->factory =& $factory;
		$this->setConfig($config);
	}

	/** 
	 * Вернуть конфиг 
	 */
	function getConfig()
	{
		$config = array();
		foreach ($this->config_vars as $v)
			$config[$v] =& $this->$v;
		return $config;
	}

	/** 
	 * Установить параметры конфигурации
	 *
	 * Можно указывать не все
	 */
	function setConfig($config)
	{
		foreach ($this->config_vars as $v)
			if (isset($config[$v])) $this->$v =& $config[$v];
			elseif (!isset($this->$v)) $this->$v = NULL;
	}

	function initialize() 
	{
		$this->initialized = True;
	}

}


/**
 * Класс RenderablePlugin - плагин с мордой
 */
class RenderablePlugin extends Plugin
{
	var $config_vars = array(
		// шаблонная переменная, куда сохранять результат
		'store_to',
	);

	function initialize() 
	{
		$this->factory->registerObserver('on_rend', array(&$this, 'rend'));
		parent::initialize();
	}

	function rend(&$ctx)
	{
	}

}

?>
