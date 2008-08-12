<?php

class Plugin
{
	/** ����� ���������� ������������ ������� */
	var $config_vars = array();
	var $inialized = False;

	function Plugin()
	{
	}

	/**
	 * ������� ������
	 */
	function getConfig()
	{
		$config = array();
		foreach ($this->config_vars as $v)
			$config[$v] =& $this->$v;
		return $config;
	}

	/**
	 * ���������� ��������� ������������
	 *
	 * ����� ��������� �� ���
	 */
	function setConfig($config)
	{
		foreach ($this->config_vars as $v)
			if (isset($config[$v])) $this->$v =& $config[$v];
			elseif (!isset($this->$v)) $this->$v = NULL;
	}

	function initialize(&$ctx, $config=NULL)
	{
		$this->rh =& $ctx;

		if (isset($config['factory'])) $this->factory =& $config['factory'];

		$this->setConfig($config);
		$this->initialized = isset($this->factory);
		return $this->initialized;
	}

}


Finder::useClass('controllers/RenderablePlugin');
?>