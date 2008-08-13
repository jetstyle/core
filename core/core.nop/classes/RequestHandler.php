<?php
class RequestHandler 
{
	protected static $instance = null;
	
	protected $pageDomains = null;

	public static function &getInstance($className = '')
	{
		if (null === self::$instance)
		{
			Finder::useClass($className);
			self::$instance = new $className();
		}

		return self::$instance;
	}
	
	public static function _404()
	{
		Finder::useLib('http');
		Http::status(404);
		RequestHandler::getInstance()->finalize('404');	
		die();
	}

	protected function __construct()	{	}

	public function init()
	{
		if (get_magic_quotes_gpc())
		{
			$this->fuckQuotes($_POST);
			$this->fuckQuotes($_GET);
			$this->fuckQuotes($_COOKIE);
			$this->fuckQuotes($_REQUEST);
		}
				
		$this->initDebug();
		$this->initDBAL();
		
		// config from DB
		if ($this->db)
		{
			Finder::useModel('DBModel');
			Config::loadFromDb('??config');
		}
		
		$this->initTPL();
		$this->initMessageSet();
		$this->initUpload();
		$this->initPrincipal();
		$this->initFixtures();
		$this->initRequestInfo();
		

		Debug :: trace("RH: init done");
	}

	public function finalize($siteMap = '')
	{
		$this->tpl->set('print_href', RequestInfo::hrefChange('', array (
			'print' => 1
		)));

		if (RequestInfo::get('print'))
		{
			$this->tpl->set('html:print', '1');
		}

		$this->tpl->parseSiteMap($siteMap);
		echo $this->tpl->get('html');
	}

	public function getPluralizeDir($classname) 
	{
		Finder::useClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words) - 1]) - 1);
		$last_word = strtolower($last_word);
		return Inflector :: pluralize($last_word);
	}

	public function redirect($href) 
	{
		if (strpos($href, "http://") !== 0)
			$href = $this->ri->_host_prot . $href;

		header("Location: $href");
		exit;
	}

	protected function initUpload()
	{
		Debug :: mark("upload");
		Finder::useClass("Upload");
		$this->upload = &Upload::getInstance();
		$this->upload->setDir(Config::get('file_dir'));
		Debug :: trace("RH: created Upload", null, "upload");
	}

	protected function initDebug() 
	{
		//инициализируем базовые объекты
		if (Config::get('enable_debug')) 
		{
			Finder::useClass("Debug");
			Debug :: init();
		} 
		else
		{
			Finder::useClass("DebugDummy");
		}
	}

	protected function initRequestInfo()
	{
		Finder::useClass('RequestInfo');
		RequestInfo::init();

		session_set_cookie_params(0, RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
		$this->tpl->set("/", RequestInfo::$baseUrl);
	}

	protected function initDBAL() 
	{
		Debug :: mark("db");
		if (Config::get('db_al')) 
		{
			Finder::useClass("DBAL");
			$this->db = & DBAL :: getInstance();
		}
		Debug :: trace("RH: created DBAL", "db", "db");
	}

	/**
	 *  Создание шаблонизатора
	 *  TODO: всякие шаблонные переменные не должны здесь устанавливаться
	 */
	protected function initTPL() 
	{
		// ВЫКЛЮЧАЕМ tpl если что
		if (Config::get('tpl_disable') === true) 
		{
			Debug :: trace("RH: TPL DISABLED");
		} 
		else 
		{
			Debug :: mark("tpl");
			Finder::useClass("TemplateEngine");
			$this->tpl = &TemplateEngine::getInstance();
			Debug :: trace("RH: created TPL", "tpl", "tpl");
		}
	}

	protected function initMessageSet()
	{
		if (Config::get('msg_disable') === true) 
		{
			Debug :: trace("RH: MSG DISABLED");
		} 
		else 
		{
			Debug :: mark("msg");
			Finder::useClass("MessageSet");
			$this->msg = & new MessageSet($this);
			Debug :: trace("RH: created MSG", null, "msg");
		}
	}

	//Инициализация принципала.
	protected function initPrincipal()
	{
		if (!$this->db) return;
		$this->principal = & new Principal($this, $this->principal_storage_model, $this->principal_security_models);

		if ($this->principal->Identify() > PRINCIPAL_AUTH) {
			$this->principal->Guest();
		}
	}

	protected function initFixtures()
	{
		if (!Config::get('use_fixtures')) return;

		Finder::useClass('Fixtures');
		$fixtures = new Fixtures($this);
		$fixtures->setDir(Config::get('app_dir').'fixtures/');
		$fixtures->load();
		$data = $fixtures->get();

		foreach ($data AS $k => $v)
		{
			$this->tpl->set($k, $v);
		}
	}
	
	public function _onCreatePage(& $page) {
	}


	// удаляем "магические" квоты из предоставленного массива
	// и всех содержащихся в нём массивов
	protected function fuckQuotes(& $a) 
	{
		if (is_array($a))
			foreach ($a as $k => $v)
				if (is_array($v))
					$this->fuckQuotes($a[$k]);
				else
					$a[$k] = stripslashes($v);
	}

	public function jsonEncode($input)
	{
		$out = array();
		if (is_array ($input))
		{
			foreach ($input as $key => $value)
			{
				if(is_array($value))
				{
					$out[] = $this->db->quote($key) . ":" . $this->jsonEncode($value);
				}
				else
				{
					$out[] = $this->db->quote($key) . ":" . $this->db->quote($value);
				}
			}
		}
		return "{" . implode(",", $out) . "}";
	}

}
?>