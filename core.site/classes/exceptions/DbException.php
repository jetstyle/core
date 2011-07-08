<?php

class DbException extends JSException
{
    private $hintMessage = '';

	public function getText()
	{
		return "<b>Mysql said:</b><div class=\"source\">".mysql_error().$this->hintMessage."</div>";
	}

    public function setHintMessage($message)
    {
        $this->hintMessage = "<br>Maybe you want: ".$message;
    }
    	
}

?>
