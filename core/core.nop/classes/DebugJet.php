<?php

class Debug
{
	var $halt_level;
	var $log;
	var $_milestone;
	var $milestone;
	var $mark = array();

	function Debug( $halt_level=0, $to_file=NULL )
	{
		$this->milestone = $this->_getmicrotime();
		$this->_milestone = $this->milestone;
		$this->mark['auto'] = $this->milestone;
		$this->trace("<b>log started.</b>");
	}

	// работа с временными отметками
	function _getmicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	// вывод лога
	function getHtml( $prefix="<div style='clear: both;'></div><div class='debug'><div>Trace log:</div><div>", $separator="</div><div>", $postfix="</div></div><div id='debug_div' style='position: absolute;'></div>" )
	{
		$out = '';
		$this->trace( "<b>log flushed.</b>");
		$out.=$prefix;
		$f=0;
		foreach ($this->log as $item)
		{
			if (!$f) {
				$f=1;
			}
			else {
				$out.=$separator;
			}
			$out.=$item;
		}
		$out.=$postfix;

		$this->log = array();

		return $out;
	}

	// вывод в лог
	function trace( $what, $label = null)
	{
		$m = $this->_getmicrotime();
		$diff = $m - $this->_milestone;
		if($label)	{
			$diff1 = $m - $this->mark[$label];
			unset($this->mark[$label]);
		}
		else
		{
			$diff1 = $m - $this->mark['auto'];
		}

		if (function_exists('memory_get_usage'))
		{
			$memory = number_format((memory_get_usage() / 1024));
		}
		$this->log[] = sprintf("[%0.4f] ",$diff).sprintf("[%0.4f] ",$diff1).( $memory ? " [$memory kb] " : "").$what;
		$this->mark['auto'] = $m;
	}

	function mark($label = 'auto')	{
		$this->mark[$label] = $this->_getmicrotime();
	}

	// умереть, тихо или громко
	function halt( $flush = 1 )
	{
		header("Content-Type: text/html; charset=windows-1251");
		if ($flush) echo $this->getHtml();
		die("prematurely dying.");
	}

	// добавить в лог запись об ошибке и возможно умереть
	function Error( $msg )
	{
		$this->Trace( "<span style='font-weight:bold; color:#ff4000;'>ERROR: ".$msg."</span>" );
		$this->Halt();
	}

}

?>