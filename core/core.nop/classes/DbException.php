<?php

class DbException extends Exception
{
	private $codes_names = array("0" => "DB SQL error", "1" => "DB Connect Error", "2" => "DB Select Error", "3"=>"DBModel Error");

	public function __toString() 
	{
		return __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n".
		"<br><b>".mysql_error()."</b>";
	}
	
}

?>