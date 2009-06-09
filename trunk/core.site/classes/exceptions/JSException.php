<?php

class JSException extends Exception
{
	protected $text = '';
    protected $humanText = '';
	
	public function __construct($msg, $text = '', $humanText = '')
	{
		$this->setText($text);
        $this->setHumanText($humanText);

		return parent::__construct($msg);
	}

	public function __toString() 
	{
		return get_class($this) . ": ".$this->message;
	}

    public function setText($text)
    {
        $this->text = $text;
    }
	
	public function getText()
	{
		return $this->text;
	}

    public function setHumanText($humanText)
    {
        $this->humanText = $humanText;
    }

	public function getHumanText()
	{
		return $this->humanText;
	}
}
?>