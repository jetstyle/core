<?php
/*
 * Parent Controller
 *
 */
class Controller 
{
	var $rh;
	
	function Controller()
	{
	}

	function initialize(&$ctx, $config=NULL) 
	{ 
		$this->rh =& $ctx; 
		if (isset($config)) 
			$this->config = array_merge($this->config, $config);
		return True;
	}

	function handle() 
	{
	}
	
}	
?>
