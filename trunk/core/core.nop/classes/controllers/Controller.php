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
			$this->config = array_merge($this->config, $config);
		return True;
	}

	function handle() 
	{
	}
	
}	
?>
