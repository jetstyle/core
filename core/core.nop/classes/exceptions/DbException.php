<?php

class DbException extends JSException
{
	public function __toString() 
	{
		return parent::__toString()."<br /><br /><b>".mysql_error()."</b>";
	}
	
}

?>