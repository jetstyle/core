<?php

/*
  Основной обработчик запроса. 
  Организует последовательность обработки и функциональное окружение. 
  Служит мостом для сообщения между собой подключаемых модулей.

  ===================

  //поток обработки

  * Requesthandler( $config_path = 'config/default.php' ) -- конструктор,
					 грузит конфиг, выполняет инициализацию, строит базовое окружение.
	 ВХОД:
		- $config_path -- путь до файла с конфигом
	 ВЫХОД:
		Базовое окружение: $rh->db, $rh->tpl, $rh->debug

  * Handle ( $ri=false ) -- Обеспечивает основную последовательность обработки запроса.
	 ВХОД:
		- $ri -- если указан, объект класса RequestInfo
		kuso@npj: потенциально не нравится, что передаётся не ссылкой, а копией.
					 обсуждение -- в имплементации метода
	 ВЫХОД:
		Строка с результатами работы.

  * InitPrincipal () -- Инициализация принципала. Функция не доработана!
	 ВХОД:
		неясно
		kuso@npj: imho -- без параметров
	 ВЫХОД:
		неясно
		kuso@npj: imho -- ссылка на объект класса-наследника от Principal

  * MapHandler( $url ) -- Выбор обработчика на основе строки запроса и карты обработчиков.
								  Поиск в конент-таблице реализовывать в наследниках.
	 ВХОД:
		$this->handlers_map -- хэш, ставящий в соответствие адресам обработчики
		$url -- строка адресу внутри сайта: catalogue/trees/pice/qa
	 ВЫХОД:
		$this->handler - имя файла обработчика. Возможно, пустое, если не нашли обработчика.
		$this->params_string -- строка, остаток строки адреса
		$this->params -- массив, остаток строки адреса, разбитый по слешам

  * _UrlTrail(&$A,$i) -- Формирует информацию об остатке адреса для обработчика. Для внутреннего использования.
	 ВХОД:
		- $A -- массив, полная строка запроса, разбитая по слэшам
		- $i -- индекс, начиная с которого нужно сформировать остаток
		kuso@npj: давай будем называть параметры "говорящим образом"
		- $URL_SEPARATED (?)
		- $start_index
	 ВЫХОД:
		$this->params
		$this->params_string

  * InitEnvironment() -- Построение стандартнго окружения. На данном уровне пуст. Перегружать в наследниках.
	 ВХОД: 
		ничего
	 ВЫХОД:
		$this->db, $this->tpl, $this->debug

  * Execute( $handler='' ) -- Запуск выбранного обработчика на исполнение.
	 ВХОД:
		- $handler -- возможно указать обработчик явно
		kuso@npj: у меня на вход давалась тройка $handler, $params, $principal
					 реально последний не использовался.
					 Если давался пустой $handler, то $handler, $params брались из $this->..
		zharik: предлагаю пока передавать только $handler. Остальное добавим по мере появления потребностей.
		kuso@npj: ок
	 ВЫХОД:
		$this->tpl->VALUES['HTML:body'] или $this->tpl->VALUES['HTML:html']

  * PrepareResult () -- Пост-обработка результатов работы.
			Если $this->tpl->VALUES['HTML:html'] пусто, то оборачивает $this->tpl->VALUES['HTML:body'] в html.html.
	 ВХОД:
		$this->tpl->VALUES['HTML:body'] или $this->tpl->VALUES['HTML:html']
	 ВЫХОД:
		строка с результатами работы

  //поиск файлов

  * FindScript ( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- Ищет скрипт по уровням проектов.
	 ВХОД:
		$type -- псевдотип скрипта, например classes, handlers, actions и пр.
		$name -- относительное имя файла в каталоге псевдокласса, без расширения 
		$level -- уровень проекта, начиная с которого нужно искать файл
					 если не задан, берётся равный самому последнему
		$dr -- направление поиска, возможные значения : -1,0,+1
		$ext -- расширение файла, обычно не указывается
		$this->DIRS -- массив корневых директорий для каждого уровня проекта,
		  для каждого уровня может быть задано:
		  $dir_name -- строка, имя корневой директории
		  array( $dir_name, $TYPES ):
			 $dir_name -- строка, имя корневой директории
			 $TYPES -- перечисление, какие типы на уровне есть
	 ВЫХОД:
		полное имя скрипта, которое можно вставить в include()
		false, если скрипт не найден

  * FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript, 
				  но в случае не обнаружения файла вываливается с ошибкой.

  * UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript_, 
				  но дополнительно инклюдит скрипт

  * UseClass( $name, $level=0, $dr=1, $ext = 'php' ) -- То же, что и UseScript, но
				$type='classes', начинает искать с 0-го уровня вверх

  * UseLib( $library_name, $file_name="" ) -- Подключить библиотеку из каталога /lib/

  * End() -- штатное завершение работы

  * Redirect( $href ) -- редирект на эту страницу
	 ВХОД:
		- $href -- полноценный урл (не "внутрисайтовый"), например, результат $ri->Href( "/" );

  //вспомогательные функции

  * _FuckQuotes (&$a) -- Удаляет квотирование в массиве и всех содержащихся в нём массивах рекурсивно.
	 ВХОД:
		- $a -- ссылка на массив, который нужно обработать
	 ВЫХОД:
		Обработанный массив $a.

  * _SetDomains () -- Функция, заполняющая поля *_domain, чтобы помогать кукам и вообще всем
	 ЗАПОЛНЯЕТ:
		- $this->base_domain
		- $this->current_domain
		- $this->cookie_domain

 */

require_once JS_CORE_DIR . 'classes/ConfigProcessor.php';

class RequestHandler extends ConfigProcessor {

	//информация об остатке адреса для обработчика
	var $params = array ();
	var $params_string = "";

	//информация об корневом обработчике
	var $handler = ''; // site/handlers/{$handler}.php
	var $handler_full = false; // =/home/..../site/handlers/{$handler}.php

	var $fixtures = array ();
	var $use_fixtures = False;

	public function __construct($config_path = 'config/default.php') {
		//пытаемся прочесть файл конфигурации
		if (is_object($config_path)) {
			config_joinConfigs($this, $config_path);
		} else
			if (@ is_readable($config_path)) {
				require_once ($config_path);
			} else {
				$uri = preg_replace("/\?.*$/", "", $_SERVER["REQUEST_URI"]);
				$page = $_REQUEST["page"];
				$uri = substr($uri, 0, strlen($uri) - strlen($page));
				$uri = rtrim($uri, "/") . "/setup";
				die("Cannot read local configurations. May be you should try to <a href='" . $uri . "'>run installer</a>, if any?");
			}

		//вычисляем base_url
		if (!isset ($this->base_url))
			$this->base_url = dirname($_SERVER["PHP_SELF"]) . (dirname($_SERVER["PHP_SELF"]) != '/' ? '/' : '');
		if (!isset ($this->base_dir))
			$this->base_dir = $_SERVER["DOCUMENT_ROOT"] . $this->base_url;
		if (!isset ($this->host_url))
			$this->host_url = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, strpos($_SERVER['SERVER_PROTOCOL'], '/'))) . '://' . $_SERVER['SERVER_NAME'] .
			 ($_SERVER['SERVER_PORT'] === '80' ? '' : ':' . $_SERVER['SERVER_PORT']);

		$this->_setDomains();

		//избавляемся от квотов
		if (get_magic_quotes_gpc()) {
			$this->_fuckQuotes($_POST);
			$this->_fuckQuotes($_GET);
			$this->_fuckQuotes($_COOKIE);
			$this->_fuckQuotes($_REQUEST);
		}

		//базовые объекты фреймворка
		//TODO: все они синглтоны
		$this->init();
	}

	public function & getPageDomain() {
		return $this->pageDomain;
	}

	//основная функция обработки запроса
	public function handle($ri = false) {
		if ($ri)
		{
			$this->ri = & $ri;
		}

		if (!isset ($this->ri)) {
			//инициализация $ri по умолчанию
			$this->ri = & new RequestInfo($this); // kuso@npj: default RI должен быть с одним параметром имхо
		}
		$this->url = $this->ri->GetUrl();

		//определение обработчика
		$this->mapHandler($this->url);

		//построение окружения
		$this->initEnvironment();

		//выполнение обработчика
		$this->execute();
		return $this->tpl->Parse("html.html");
	}

	// Алиасы, специфичные для RH
	public function useClass($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		$this->useScript("classes", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	// Алиасы, специфичные для RH
	public function useModel($name, $level = 0, $dr = 1, $ext = 'php', $withSubDirs = false, $hideExc = false) {
		$this->useScript("classes/models", $name, $level, $dr, $ext, $withSubDirs, $hideExc);
	}

	public function useLib($library_name, $file_name = "") {
		// library is near core, library have no levels
		//$direction = 0;
		// lucky@npj: фиг вам -- где угодно. сначала в приложении, затем в core
		$direction = 1;
		$level = 0;
		// usually library have one file to link itself
		if ($file_name == "")
			$file_name = $library_name;
		$ext = "php";

		$this->useScript($this->lib_dir, $library_name . "/" . $file_name, $level, $direction, $ext);
	}

	public function useModule($name, $type = NULL) {
		$this->useClass('ModuleLoader');
		$o = & new ModuleLoader();
		$o->initialize($this);
		$o->load($name);
		return $o->data;
	}
	
	public function getPluralizeDir($classname) {
		$this->UseClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words) - 1]) - 1);
		$last_word = strtolower($last_word);
		return Inflector :: pluralize($last_word);
	}

	public function redirect($href) {
		if (strpos($href, "http://") !== 0)
			$href = $this->ri->_host_prot . $href;

		header("Location: $href");
		exit;
	}
	
	protected function execute() {
		$this->page->handle();
		$this->page->rend();
		$this->showSiteMap();
	}

	protected function init() {
		$this->initDebug();
		$this->initDBAL();
		$this->initTPL();
		$this->initMessageSet();
		$this->initUpload();
		$this->initPrincipal();
		
		Debug :: trace("RH: constructor done");
	}

	protected function initUpload()
	{
		Debug :: mark("upload");
		$this->UseClass("Upload");
		$this->upload = & new Upload($this, $this->project_dir . "files/", '', 'files/');
		Debug :: trace("RH: created Upload", null, "upload");
	}
	
	protected function initDebug() {
		//инициализируем базовые объекты
		if ($this->enable_debug) {
			$this->useClass("Debug");
			Debug :: init();
		} else {
			$this->useClass("DebugDummy");
		}
	}

	protected function initDBAL() {
		Debug :: mark("db");
		if ($this->db_al) {
			$this->UseClass("DBAL");
			//			$this->db =& new DBAL( $this );
			$this->db = & DBAL :: getInstance($this);
			if ($this->db_set_encoding) {
				$this->db->Query("SET NAMES " . $this->db_set_encoding);
			}
		}
		Debug :: trace("RH: created DBAL", "db", "db");
	}

	/**
	 *  Создание шаблонизатора
	 *  TODO: всякие шаблонные переменные не должны здесь устанавливаться
	 */
	protected function initTPL() {
		// ВЫКЛЮЧАЕМ tpl если что
		if ($this->tpl_disable === true) {
			Debug :: trace("RH: TPL DISABLED");
		} else {
			Debug :: mark("tpl");
			$this->UseClass("TemplateEngine");
			$this->tpl = & new TemplateEngine($this);
//			$this->tpl->set('/', $this->base_url);
			Debug :: trace("RH: created TPL", "tpl", "tpl");
		}
	}

	protected function initMessageSet() {
		if ($this->msg_disable === true) {
			Debug :: trace("RH: MSG DISABLED");
		} else {
			Debug :: mark("msg");
			$this->UseClass("MessageSet");
			$this->msg = & new MessageSet($this);
			$this->tpl->msg = & $this->msg;
			Debug :: trace("RH: created MSG", null, "msg");
		}
	}

	//Инициализация принципала.
	protected function initPrincipal() {
		$this->principal = & new Principal($this, $this->principal_storage_model, $this->principal_security_models);

		if ($this->principal->Identify() > PRINCIPAL_AUTH) {
			$this->principal->Guest();
		}
	}

	// функция, заполняющая поля *_domain, чтобы помогать кукам и вообще всем
	protected function _setDomains() {
		if (!isset ($this->base_domain))
			$this->base_domain = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		if (!isset ($this->current_domain))
			$this->current_domain = preg_replace("/^www\./i", "", $_SERVER["HTTP_HOST"]);
		if (!isset ($this->cookie_domain))
			// lucky@npj: see http://ru.php.net/manual/ru/function.setcookie.php#49350
			$this->cookie_domain = strpos($this->base_domain, '.') === false ? false : "." . $this->base_domain;

		session_set_cookie_params(0, "/", $this->cookie_domain);
	}

	protected function mapHandler($url) {
		$this->pageDomain = new PageDomain($this);

		if ($page = & $this->pageDomain->findPageByUrl($url)) {
			$this->page = & $page;
			$this->data = $page->config;
			$this->params = $page->params;
			$this->path = $page->path;
		} else {
			$this->page = & $this->pageDomain->findPageByClass('_404');
		}
	}

	//Построение стандартного окружения.
	protected function initEnvironment() {
		// на этом уровне включает только заполнение очень полезной
		// шаблонной переменной "/", соответствующей корню сайта
		$this->tpl->set("/", $this->ri->Href(""));
//		$this->tpl->set("lib", $this->ri->Href($this->lib_href_part) . "/");
//		$this->tpl->setRef("SITE", $this);
	}

	

	

//	function error($msg) {
//		trigger_error($msg, E_USER_ERROR);
//	}

	public function useFixture($type, $name) {

		if (!array_key_exists($name, $this->fixtures)) {
			if ($s = $this->FindScript($type, $name, false, -1, 'yml')) {
				if (!class_exists('Spyc'))
					$this->useLib('spyc');
				$this->fixtures[$name] = Spyc :: YAMLLoad($s);
			} else
				if ($s = $this->FindScript($type, $name, false, -1, 'php')) {
					$tpl = & $this->tpl;
					$this->fixtures[$name] = include $s;
				} else {
					$this->fixtures[$name] = NULL;
				}
		}
		return isset ($this->fixtures[$name]) ? $this->fixtures : NULL;

	}
//
	public function _onCreatePage(& $page) {
	}

	protected function showSiteMap() 
	{
		//TODO: extract and document setting of print params
		//nop
		$this->tpl->set('print_href', $this->ri->hrefPlus('', array (
			'print' => 1
		)));

		$conf = $this->site_map[$this->site_map_path];

		$this->_showSiteMapPath($conf);
		
		//nop: again print params
		if ($this->ri->get('print')) 
		{
			$this->tpl->set('html:print', '1');
		}
	}
	
	/**
	 * Построить один массив сайтмапа
	 * вообще этому пора делать extract class
	 * nop
	 */
	public function _showSiteMapPath($conf=array())
	{
		if (!empty($conf)) 
		{
			foreach ($conf as $k => $v) 
			{
				//массив с шаблонами/значениями/инстркуциями
				if (is_array($v)) 
				{
					$_v = "";
					foreach ($v as $v1)
						$_v .= $this->_constructValue($v1);
					$this->tpl->set($k, $_v);
				} 
				else
					//значение переменной
					$this->tpl->set($k, $this->_constructValue($v));
			}
		}	
	}

	/*
	* Вспомогательная функция для сайтмапа (this->End())
	*/
	protected function _constructValue($v) {
		if ($v[0] == "@") //отпарсить шаблон
			{
			return $this->tpl->parse(substr($v, 1));
		}
		elseif ($v[0] == "{") //значение шаблонной переменной
		{
			return $this->tpl->get(substr(substr($v, 2), 0, -2));
		} else //вставить текст
			return $v;
	}

	protected function prepareResult($after_execute) {
		/*
		На этом уровне проверяем, нужно ли оборачивать результат в html.html
		Для дополнительной пост-обработки окружения перегружать этот метод в наследниках.
		*/
		$template = isset ($this->page->template) ? $this->page->template : 'html.html';

		$tpl = & $this->tpl;
		if (!$tpl->is("HTML:html")) {
			if (!$tpl->is("HTML:body"))
				$tpl->set("HTML:body", $after_execute);
			return $tpl->parse($template);
		} else
			return $tpl->get("HTML:html");
	}

	// удаляем "магические" квоты из предоставленного массива
	// и всех содержащихся в нём массивов
	protected function _fuckQuotes(& $a) {
		if (is_array($a))
			foreach ($a as $k => $v)
				if (is_array($v))
					$this->_FuckQuotes($a[$k]);
				else
					$a[$k] = stripslashes($v);
	}

}
?>