<?php

class JSException extends Exception
{
	protected $text = '';
	
	public function __construct($msg, $text = '') 
	{
		$this->text = $text;
		return parent::__construct($msg);
	}

	public function __toString() 
	{
		return get_class($this) . ": ".$this->message;
	}
	
	public function getText()
	{
		return $this->text;
	}
}
?>