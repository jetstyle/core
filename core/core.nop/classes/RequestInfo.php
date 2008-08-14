<?php
/**
 * Работа с УРЛом
 * @author lunatic <lunatic@jetstyle.ru>
 */
class RequestInfo
{
	const STATE_USE = 0;
	const STATE_IGNORE = 1;
	const METHOD_GET = "get";
	const METHOD_POST = "post";

	static public $host = '';
	static public $hostProt = '';

	static public $pageUrl = '';
	static public $baseUrl = '';
	static public $baseFull = '';

	static public $baseDomain = '';
	static public $cookieDomain = '';

	static private $data = array();
	static private $params = array();
	static private $denyForeignPosts = false;
	static private $_compiled_ready = false;
	static private $_compiled = array();
	static private $values = array();

	private function __construct(){}

	/**
	 * Инициализация
	 *
	 */
	static public function init()
	{
		if (self::$denyForeignPosts)
		{
			if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')
			{
				if ($_SERVER['HTTP_HOST'] OR $_ENV['HTTP_HOST'])
				{
					$http_host = ($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']);
				}
				else if ($_SERVER['SERVER_NAME'] OR $_ENV['SERVER_NAME'])
				{
					$http_host = ($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_ENV['SERVER_NAME']);
				}
				if ($http_host AND $_SERVER['HTTP_REFERER'])
				{
					$referrer_parts = parse_url($_SERVER['HTTP_REFERER']);
					$http_host = preg_replace('#^www\.#i', '', $http_host);
					$thishost = preg_quote($http_host . !empty($referrer_parts['port']) ? ":$referrer_parts[port]" : '', '#');
					$refhost = $referrer_parts['host'] . !empty($referrer_parts['port']) ? ":$referrer_parts[port]" : '';

					if (!preg_match('#' . $thishost . '$#siU', $refhost))
					{
						throw new Exception("POST requests from foreign hosts are not allowed.");
					}
				}
			}
		}

		self::$host = preg_replace('/:.*/','',$_SERVER["HTTP_HOST"]);
		self::$hostProt = "http://".$_SERVER["HTTP_HOST"];
		self::$baseFull = "http://".self::$host.Config::get('base_url');
		self::$baseUrl  = Config::get('base_url');

		self::$pageUrl = $_REQUEST['page'];

		self::load($_GET);
		self::free('page');

		self::$baseDomain = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		self::$cookieDomain = strpos(self::$baseDomain, '.') === false ? false : "." . self::$baseDomain;
	}

	/**
	 * Загрузка переменных
	 *
	 * @param array $values
	 */
	static public function load($values)
	{
		if(!is_array($values) || empty($values))	return;
		foreach($values AS $key => $value)
		{
			self::$data[$key] = $value;
		}
	}

	static public function free($key)
	{
		unset(self::$data[$key]);
	}


	static public function get($key)
	{
		return self::$data[$key];
	}

	static public function set($key, $value)
	{
		self::$data[$key] = $value;
	}

	/**
	 * Добавление / изменение / удаление переменных из УРЛа
	 *
	 * @param string $url
	 * @param array $key
	 * @return string
	 */
	static public function hrefChange($url, $key)
	{
		if (!$url)	$url = self::$baseUrl.self::$pageUrl;
		if (!is_array($key) || empty($key)) return $url;

		$d = self::$data;

		foreach($key AS $k => $v)
		{
			if ($d[$k])
			{
				$d[$k] = $v;
			}
			else if ($v)
			{
				$d[$k] = $v;
			}
		}

		foreach($d AS $k => $v)
		{
			if ($v)
			{
				$d[$k] = $k.'='.urlencode($v);
			}
		}

		if (is_array($d))
		{
			$line = @implode('&', $d);
			if ($line)
			{
				$url = $url.'?'.$line;
			}
		}
		return $url;
	}

	public static function pack( $method=self::METHOD_GET, $bonus="", $only="" ) // -- упаковать в строку для GET/POST запроса
	{
		if (!self::$_compiled_ready)
		{
			self::$_compiled[self::METHOD_GET ] = "";
			self::$_compiled[self::METHOD_POST] = "";
			$f=0;
			foreach(self::$values as $k=>$v)
				if( $v!='' )
					if (($only == "") || (strpos($k, $only) === 0))
					{
						if (is_array($v))
						{
							$v0 = array_map(htmlspecialchars, $v);
							$v1 = array_map(urlencode, $v);
						}
						else
						{
							$v0 = htmlspecialchars($v);
							$v1 = urlencode($v);
						}
						if ($f) $this->_compiled[self::METHOD_GET ].=$this->s; else $f=1;
						$this->_compiled[self::METHOD_GET ] .= $k."=".$v1;
						$this->_compiled[self::METHOD_POST] .= "<input type='hidden' name='".$k."' value='".$v0."' />\n";
					}
			self::$_compiled_ready = 1;
		}
		$data = self::$_compiled[$method];
		if ($method == self::METHOD_POST) return $data.$bonus;
		if ($bonus != "")
		if ($data != "") $data=$this->q.$data.$this->s.$bonus;
			else $data.=$this->q.$bonus;
			else if ($data != "") $data = $this->q.$data;
		return $data;
	}
}
?>