<?php

class Link
{
	var $rh = null;
	var $allowed_proto = array();
	
	function Link(&$rh)
	{
		$this->rh = &$rh;
		$this->allowed_proto = explode(',', $this->rh->link_allowed_protocols);
	}
	
	function formatLink($val)
	{
		foreach($this->allowed_proto AS $r)
		{
			if($r && !(strpos($val, $r) === false))
			{
				return $val;
			}
		}
		return $this->rh->ri->Href(ltrim($val, '/'));		
	}
	
}

?>