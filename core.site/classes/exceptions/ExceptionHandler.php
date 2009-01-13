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
	
	/**
	 * Reaction on exception
	 * 
	 * ex.
	 * $config = array(
	 * 	'FileNotFoundException' => EXCEPTION_SHOW,
	 * 	'DbException' => EXCEPTION_MAIL + EXCEPTION_SILENT
	 * );
	 *
	 * @var array
	 */
	private $config = array();
	
	/**
	 * Methods to execute
	 *
	 * @var array
	 */
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
			foreach ($this->methodsByConst AS $action => $method)
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
						* {
							margin: 0; padding: 0;
							font-size: 100.01%; }
						html { }
						body {
							padding: 20px; 
							font-family: Arial, sans-serif; }
						
						ol {padding-top: 20px;}
						ol li {margin-bottom: 15px;}
						
						.info td {vertical-align: top;}
						
						.backtrace {padding-top: 30px; padding-left: 20px; min-width: 500px;}
						.backtrace .backtrace-file {font-size: 80%; margin-top: 5px;}
						
						.detailed-info {padding-top: 30px; padding-left: 30px;}
						
						.clearer {clear: both;}
						tt { color:#666600; background:#ffffcc; padding: 5px; font-family: Arial, sans-serif;}
						.warning {color: red; font-weight: bold;}
						.source {margin-top: 5px; background-color: #EAEAEA; padding: 10px; font-family: Arial, sans-serif;}
					</style>
				</head>
				<body>';
		
		echo '<div class="message">';
		
		if ("Exception" == get_class($exceptionObj))
		{
			echo $exceptionObj->getMessage();
		}
		else
		{
			echo $exceptionObj;
		}

		echo "</div>";
		
//		echo "<br class=\"clearer\" />";
		
		echo "<table class=\"info\"><tr><td class=\"backtrace\">";
		echo "<b>Backtrace</b>:<br />";
		echo $this->getTrace($exceptionObj->getTrace());
		echo "</td><td class=\"detailed-info\">";
		
//		echo "<b>Info</b>:<br />";
		if ("Exception" != get_class($exceptionObj))
		{
			echo $exceptionObj->getText();
		}
		echo "</td></tr></table>";
		
		echo '</body></html>';
	}

	private function getTrace($data)
	{
		$projectDir = '';
		if (class_exists('Config'))
		{
			$projectDir = Config::get('project_dir');
		}
		
		$res = '<ol>';
		foreach ($data as $key => $value)
		{
			$res .= '<li>'.$value['class'].$value['type'].$value['function'];
			if ($value['file'])
			{
				$value['file'] = str_replace($projectDir, '', $value['file']);
				$res .= '<div class="backtrace-file">file: '.$value['file'].' (line: '.$value['line'].')</div></li>';
			}
		}
		$res .= '</ol>';
		return $res;
	}
}

?>