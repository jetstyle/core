<?php

class TplException extends Exception
{
	private $codes_names = array("0" => "SubTemplate not found", "1" => "Action not found");

	public function __construct($message = null, $code = 0) 
	{
		ob_end_clean();
		parent::__construct($message, $code);
	}

	public function __toString() 
	{
		return __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n";
	}
}

?>