<?php

class FileNotFoundException extends Exception
{
	private $codes_names = array("0" => "File not found", "1" => "Tpl file not found");

	public function __toString() 
	{
		return __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n";
	}
	
}

?>