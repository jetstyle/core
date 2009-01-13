<?php

class DbException extends JSException
{
	public function getText()
	{
		return "<b>Mysql said:</b><div class=\"source\">".mysql_error()."</div>";
	}
	
}

?>