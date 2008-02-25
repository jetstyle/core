<?php
/*
  ����� ��������� ����������.
*/

define("EXCEPTION_IGNORE", 1);
define("EXCEPTION_SILENT", 2);
define("EXCEPTION_MAIL",   4);
define("EXCEPTION_SHOW",   8);

class ExceptionHandler
{
	static private $instance;
	private $config;
	private $methodsByConst = array(EXCEPTION_IGNORE => "ignore",
	                                EXCEPTION_SILENT => "silent",
	                                EXCEPTION_MAIL   => "mail",
	                                EXCEPTION_SHOW   => "show",
                                   );

	private $silentDieMsg =  "� ���������, ��������� ������.";

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
		//�������� ��� ������ � ������ exception'��
		$className = get_class($exceptionObj);
		if (!isset($this->config[$className]))
		{
			$method = $this->methodsByConst[EXCEPTION_SILENT];
			$this->$method($exceptionObj);
			die();
		}

		$actions = array();
		foreach ($this->methodsByConst as $action=>$method)
		{
			if ($this->config[$className] & $action)
				$actions[$action] = $method;
		}

		foreach ($actions as $method)
			$this->$method($exceptionObj);

		if (!$actions[EXCEPTION_IGNORE])
			die();
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
		$subj = "=?windows-1251?b?" . base64_encode("������ �� �����") . "?=";
		$text = "������ �� �����<br />";
		$text .= "Url: <b>" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "</b><br />";
		$text .= "����: <b>" . date("d.m.Y G:i:s") . "</b><br />";
		$text .= $exceptionObj->__toString();

		$fromaddress = "dzjuck@gmail.ru";
		$headers = "From: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Reply-To: =?windows-1251?b?" . base64_encode("Exception robot") . "?= <".$fromaddress.">\r\n";
		$headers .= "Content-Type: text/html; charset=\"windows-1251\"";
		$headers .= "Content-Transfer-Encoding: 8bit";

		//var_dump($headers);
		//echo $text;
//		$res = mail("dzjuck@gmail.com", $subj, $text, $headers);
		//var_dump($res);
	}

	private function show($exceptionObj)
	{
		echo $exceptionObj;
		var_dump(ini_get("memory_limit"));
		echo "<br /><br /><b>Backtrace</b>:<br />";

		ob_start();
//		debug_print_backtrace();
//		print_r($exceptionObj->getTrace());
//		is_array($exceptionObj->getTrace()));

//$this->_getTrace($exceptionObj->getTrace());
		echo "<pre>";
		print_r($this->getTrace($exceptionObj->getTrace()));
		echo "</pre>";
		$_ = ob_get_contents();
		ob_end_clean();
		$_ = preg_replace("/\[db\_password\] \=>[^\,]+\,/", "", $_);
		$_ = preg_replace("/\[db\_user\] \=>[^\,]+\,/", "", $_);
//		echo '<pre>';
		echo $_;
//		echo '</pre>';
	}

	function getTrace($data)
	{
		$res = array();
		foreach ($data as $key => $value)
		{
			if (is_array($value))
				$res[$key] = $this->getTrace($value);
			elseif (is_object($value))
				$res[$key] = "Is Object '" . get_class($value) . "'";
			else
				$res[$key] = $value;
		}
		return $res;
	}
}

?>