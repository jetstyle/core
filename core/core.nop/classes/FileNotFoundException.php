<?php

class FileNotFoundException extends Exception
{
	private $codes_names = array("0" => "File not found", "1" => "Tpl file not found");

	public function __construct($msg, $code, $file_name="", &$rh=null) 
	{
		$this->file_name = $file_name;
		$this->rh =& $rh;

		return parent::__construct($msg, $code);
	}

	public function __toString() 
	{
		$ret = __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n";
		
		/**
		 * Для шаблонов хотим показать откуда был вызов
		 * (не по backtrace а по понятиям шаблонных инклудов)
		 * QUICKSTART-138
		 * nop
		 */
		if ($this->code==1)
		{
			$traces = $this->getTrace();

			$needle = '@'.$this->file_name.".html";
			foreach ($traces as $k=>$trace)
			{
				if ($can_seek)
				{
					$this->no_trace = true;
					$from = $trace['file'];
					$from = $traces[$k+2]['args'][0];
					
					$file_source = $this->rh->tpl_root_dir.$this->rh->tpl_skin."/templates/".$from;
					$contents = file($file_source);
					$contents = str_replace($needle, "<font color='red'>".$needle."</font>", $contents);

				  	$ret .= "<br><div style='background-color:#DDDDDD'> ";
				  	$ret .= "<span style='float:left'><b>Parsed from: <a href='#' onclick='document.getElementById(\"exc_".$k."\").style.display= (document.getElementById(\"exc_".$k."\").style.display==\"\" ? \"none\" : \"\" ); document.getElementById(\"exc_".$k."\").style.backgroundColor=\"#EEEEEE\"; return false;'>".$from."</a></b></span>
				  	<span style='float:right'>".$file_source."</span>
				  	</div>";
				  	$ret .= "<div style='display:none' id=\"exc_".$k."\"><br>" . nl2br(implode("\n", $contents)) . "</div></p>";

					break;
				}
				
				if ( $this->in_args($needle, $trace['args']) )
				{
					$can_seek = true;	
				}
			} 
			
		}
			
		return $ret;
	}
	
	protected function in_args($needle, $args)
	{
		$ret = false;
		foreach ($args as $arg)
		{
			if (is_array($arg))
			{
				foreach ($arg as $ar)
				{
					if ($ar==$needle)
					{
						$ret = true;	
						break;		
					}
				}
			}
			else if ($arg==$needle)
			{
				$ret = true;	
				break;	
			}
		}
		return $ret;
	}
}

?>