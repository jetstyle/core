<?php
/*
  Класс обработки исключений.
*/

define("EXCEPTION_IGNORE",      1);
define("EXCEPTION_SILENT",      2);
define("EXCEPTION_MAIL",        4);
define("EXCEPTION_SHOW",        8);
define("EXCEPTION_LOG",         16);
define("EXCEPTION_VERBOSE_LOG", 32);

class ExceptionHandler
{
	static private $instance;
	
	/**
	 * Reaction on exception
	 * 
	 * ex.
	 * $config = array(
	 * 	'FileNotFoundException' => EXCEPTION_SHOW,
	 * 	'DbException' => EXCEPTION_MAIL | EXCEPTION_SILENT
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
                                    EXCEPTION_LOG    => "log",
                                    EXCEPTION_VERBOSE_LOG => "verboseLog",
                                   );

	private $silentDieMsg =  "К сожалению, произошла ошибка.";

    private $htmlBegin = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

                            .example { color:#666600; background:#ffffcc; padding: 0 2px; font-size:115% }
						</style>
					</head>
					<body>';

    private $htmlEnd = '</body></html>';

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
		
		if (!$this->config[$className] && $this->config['all'])
		{
			$className = 'all';
		}
	
		if ($this->config[$className])
		{
			foreach ($this->methodsByConst AS $action => $method)
			{
				if ($this->config[$className] & $action )
				{
					$actions[$action] = $method;
				}
			}
		}
		else
		{
			$actions[EXCEPTION_SILENT] = $this->methodsByConst[EXCEPTION_SILENT];
		}
		
		foreach ($actions as $method)
		{
			$this->$method($exceptionObj);
		}

		if (!$actions[EXCEPTION_IGNORE])
		{
			die('');
		}
	}
	
	public function getMethod($exceptionObj)
	{
		$className = get_class($exceptionObj);
		
		if (!$this->config[$className] && $this->config['all'])
		{
			$className = 'all';
		}
		
		return	$this->config[$className];
	}

	private function ignore($exceptionObj)
	{
	}

	private function silent($exceptionObj)
	{
        $result = $this->htmlBegin;
        
        $result .= '<div class="message">';
        $result .= $this->silentDieMsg;
        $result .= '</div>';

        if (method_exists($exceptionObj, 'getHumanText'))
        {
            $result .= '<br />';
            $result .= '<div class="message">';
            $result .= $exceptionObj->getHumanText();
            $result .= '</div>';
        }

        $result .= $this->htmlEnd;
        echo $result;
	}

	private function mail($exceptionObj)
	{
		$hash = md5(serialize($exceptionObj));
		$cacheDir = Config::get('cache_dir').'exceptions/mail/';
		
		if (!file_exists($cacheDir))
		{
			$result = @mkdir($cacheDir, 0775, true);
			if (!$result)
			{
				return;
			}
		}
		
		if (!is_writable($cacheDir))
		{
			return;
		}
		
		$cacheFile = $cacheDir.$hash;
		
		if (file_exists($cacheFile))
		{
			$mtime = filemtime($cacheFile);
			if ( ($mtime + 600) > time())
			{
				return;
			}
			else
			{
				file_put_contents($cacheFile, '');
			}
		}
		else
		{
			file_put_contents($cacheFile, '');
		}
		
		if (!Config::exists('base_url'))
		{
			$baseUrl = $_SERVER['SCRIPT_NAME'];
			$baseUrl = rtrim(substr($baseUrl, 0, strrpos($baseUrl, "/") + 1), '/');
			$baseUrl = rtrim(substr($baseUrl, 0, strrpos($baseUrl, "/") + 1), '/');
			$baseUrl .= '/';
		}
		else
		{
			$baseUrl = Config::get('base_url');
		}
		
		
		//mail now
		$subj = "=?windows-1251?b?" . base64_encode(rtrim($_SERVER["HTTP_HOST"].$baseUrl, '/')) . "?=";
		$text = $this->getHtml($exceptionObj, true);
				
		$fromaddress = "bugs@jetstyle.ru";
		
		$headers = "From: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Reply-To: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Content-Type: text/html; charset=\"windows-1251\"";
		$headers .= "Content-Transfer-Encoding: 8bit";
		
		mail('<bugs@jetstyle.ru>', $subj, $text, $headers);
	}

    private function verboseLog($exceptionObj)
    {
        $dir = Config::get('cache_dir').'exceptions/'.date('Y/m/d').'/';

		if (!file_exists($dir))
		{
			$result = @mkdir($dir, 0775, true);
			if (!$result)
			{
				return;
			}
		}

		if (!is_writable($dir))
		{
			return;
		}

        $className = get_class($exceptionObj);
        $className = preg_replace('/([A-Z]+)([A-Z])/','\1_\2', $className);
        $className = strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2', $className));

        $file = $dir.date('H_i').'_'.$className.'';

        if (file_exists($file))
        {
            return;
        }

        $text = $this->getPlain($exceptionObj, true);

        file_put_contents($file, $text);
    }

    private function log($exceptionObj)
    {
        $dir = Config::get('cache_dir').'exceptions/';

		if (!file_exists($dir))
		{
			$result = @mkdir($dir, 0775, true);
			if (!$result)
			{
				return;
			}
		}

		if (!is_writable($dir))
		{
			return;
		}

        $file = $dir.'log';

        if ($handle = fopen($file, 'a'))
        {
             $text = date('d.m.Y H:i:s').' ';

            if ("Exception" == get_class($exceptionObj))
            {
                $text .= $exceptionObj->getMessage();
            }
            else
            {
                $text .= $exceptionObj;
            }

            $text .= "\r\n";

            fwrite($handle, $text);
            fclose($handle);
        }
    }

	private function show($exceptionObj)
	{
		ob_end_clean();
        if (defined('COMMAND_LINE') && COMMAND_LINE)
        {
            echo $this->getPlain($exceptionObj);
        }
        else
        {
            echo $this->getHtml($exceptionObj);
        }
		
	}

	private function getHtml($exceptionObj, $extended = false)
	{
        $result = $this->htmlBegin;

        $result .= '<div class="message">';

        if ($extended)
        {
            $result .= '<div class="date">' . date("d.m.Y H:i:s") . '</div>';
            $result .= '<div class="url">http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"].'</div>';
            $result .= '<br />';
        }

        if ("Exception" == get_class($exceptionObj))
        {
            $result .= $exceptionObj->getMessage();
        }
        else
        {
            $result .= $exceptionObj;
        }

        $result .= "</div>";

        $result .= "<table class=\"info\"><tr><td class=\"backtrace\">";
        $result .= "<b>Backtrace</b>:<br />";
        $result .= $this->getTrace($exceptionObj->getTrace());
        $result .= "</td><td class=\"detailed-info\">";

        if ("Exception" != get_class($exceptionObj))
        {
            $result .= $exceptionObj->getText();
        }
        $result .= "</td></tr></table>";

        $result .= $this->htmlEnd;
		
		return $result;
	}

    private function getPlain($exceptionObj, $extended = false)
    {
        $result = '';

        if ($extended)
        {
            $result .= date("d.m.Y H:i:s");
            $result .= "\r\n";
            $result .= 'http://'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            $result .= "\r\n";
            $result .= "\r\n";
        }

        if ("Exception" == get_class($exceptionObj))
        {
            $result .= $exceptionObj->getMessage();
        }
        else
        {
            $result .= $exceptionObj;
        }
        $result .= "\n\n";

        if ("Exception" != get_class($exceptionObj))
        {
            $result .= $exceptionObj->getText();
            $result .= "\n\n";
        }

        $result .= $this->getTrace($exceptionObj->getTrace(), true);
        return $result;
    }
	
	private function getTrace($data, $plain = false)
	{
		$projectDir = '';
		if (class_exists('Config'))
		{
			$projectDir = Config::get('project_dir');
		}
		
		if ($plain)
		{
			$i = count($data);
			foreach ($data as $key => $value)
			{
				$res .= $i--.'. '.$value['class'].$value['type'].$value['function'];
				if ($value['file'])
				{
					$value['file'] = str_replace($projectDir, '', $value['file']);
					$res .= "\n";
					$res .= 'file: '.$value['file'].' (line: '.$value['line'].')';
				}
				$res .= "\n\n";
			}
		}
		else
		{
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
		}
		
		return $res;
	}
}

?>