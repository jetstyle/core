<?php

class DbException extends Exception
{
	private $codes_names = array("0" => "Sql error", "1" => "Db connect error", "2" => "Database select error");

	public function __toString() 
	{
		return __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n".
		"<br><b>".mysql_error()."</b>";
	}
	
}

?>