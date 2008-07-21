<?php
/*
  Класс обработки исключений.
*/

define("EXCEPTION_IGNORE", 1);
define("EXCEPTION_SILENT", 2);
define("EXCEPTION_MAIL",   4);
define("EXCEPTION_SHOW",   8);

class ExceptionHandler
{
	static private $instance;
	private $config;
	private $methodsByConst = array(
									EXCEPTION_IGNORE => "ignore",
	                                EXCEPTION_SILENT => "silent",
	                                EXCEPTION_MAIL   => "mail",
	                                EXCEPTION_SHOW   => "show",
                                   );

	private $silentDieMsg =  "К сожалению, произошла ошибка.";

	static public function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {}

	public function init($config)
	{
		$this->config = $config;
	}

	public function process($exceptionObj)
	{
		$actions = array();
		
		//выясняем что делать с данным exception'ом
		$className = get_class($exceptionObj);
		if (!isset($this->config[$className]))
		{
			$actions[EXCEPTION_SHOW] = $this->methodsByConst[EXCEPTION_SHOW];
		}
		else
		{	
			foreach ($this->methodsByConst as $action=>$method)
			{
				if ($this->config[$className] & $action)
				{
					$actions[$action] = $method;
				}
			}
		}

		foreach ($actions as $method)
		{
			$this->$method($exceptionObj);
		}

		if (!$actions[EXCEPTION_IGNORE])
		{
			die();
		}
	}

	private function ignore($exceptionObj)
	{
	}

	private function silent($exceptionObj)
	{
		echo $this->silentDieMsg;
	}

	private function mail($exceptionObj)
	{
		//mail now
		$subj = "=?windows-1251?b?" . base64_encode("Ошибка на сайте") . "?=";
		$text = "Ошибка на сайте<br />";
		$text .= "Url: <b>" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "</b><br />";
		$text .= "Дата: <b>" . date("d.m.Y G:i:s") . "</b><br />";
		$text .= $exceptionObj->__toString();

		$fromaddress = "dzjuck@gmail.ru";
		$headers = "From: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Reply-To: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Content-Type: text/html; charset=\"windows-1251\"";
		$headers .= "Content-Transfer-Encoding: 8bit";
	}

	private function show($exceptionObj)
	{
		ob_end_clean();
		
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<?xml version="1" encoding="windows-1251"?>
				<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
				<head>
					<style type="text/css">
						.backtrace li {margin-bottom: 15px;}
						.backtrace .backtrace-file {font-size: 80%; margin-top: 5px;}
					</style>
				</head>
				<body>';
		
		if ("Exception" == get_class($exceptionObj))
		{
			echo $exceptionObj->getMessage();
		}
		else
		{
			echo $exceptionObj;
		}

		if ($exceptionObj->no_trace) return;
		
		echo "<br /><br /><b>Backtrace</b>:<br />";
		
		echo $this->getTrace($exceptionObj->getTrace());
		
		echo '</body></html>';
	}

	function getTrace($data)
	{
		$res = '<ol class="backtrace">';
		foreach ($data as $key => $value)
		{
			$res .= '<li>'.$value['class'].$value['type'].$value['function'].'<div class="backtrace-file">'.$value['file'].' (line: '.$value['line'].')</div></li>';
		}
		$res .= '</ol>';
		return $res;
	}
}

?>