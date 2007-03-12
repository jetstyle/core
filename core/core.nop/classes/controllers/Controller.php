<?php
/*
 * Parent Controller
 *
 */
class Controller 
{
	var $rh;
	
	function Controller(&$rh)
	{
		$this->rh =& $rh;
	}

	function initialize()
	{
		return True;
	}

	function handle() 
	{
		$this->initialize();
	}
	
}	
?>
