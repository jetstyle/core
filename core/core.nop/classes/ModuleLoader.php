<?php

require_once 'TypeLoader.php';

class ModuleLoader extends TypeLoader
{

	function initialize(&$ctx, $config=NULL)
	{
		parent::initialize($ctx, $config);

		if (!isset($this->config_name)) $this->config_name = 'config';
	}

	function load($name, $level=0, $dr=1, $ext = 'php' )
	{
		parent::load($name, $level, $dr, $ext);

		if (isset($this->data))
		{
			$loader =& new ConfigLoader();
			$loader->seeConfig($this->data, $this->namespace.'/'. $name, $this->config_name);
		}
	}


}

?>
