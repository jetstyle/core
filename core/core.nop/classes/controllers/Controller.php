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

	function handle() 
	{
		$this->_handle();
	}
	
}	
?>
