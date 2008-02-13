<?php
/*
 * Parent Controller
 *
 */

class Controller extends Configurable
{
	var $rh;
	var $config = array();
	
	function Controller()
	{
	}

	function initialize(&$ctx, $config=NULL) 
	{ 
		parent::initialize($ctx, $config);
		if (isset($config)) 
//			$this->config = array_merge($this->config, $config);
			$this->config = $this->add_config($config);
		return True;
	}

	function handle() 
	{
	}

	private function add_config($config)
	{
		if (is_object($config) && $config instanceof DataContainer)
			$config = $config->getData();
		return array_merge($this->config, $config);
	}
}	
?>
