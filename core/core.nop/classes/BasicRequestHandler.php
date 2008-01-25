<?php
/*
  Основной обработчик запроса. 
  Организует последовательность обработки и функциональное окружение. 
  Служит мостом для сообщения между собой подключаемых модулей.

  ===================

  //поток обработки

  * BasicRequesthandler( $config_path = 'config/default.php' ) -- конструктор,
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

class BasicRequestHandler extends ConfigProcessor {

	//информация об остатке адреса для обработчика
	var $params = array();
	var $params_string = "";

	//информация об корневом обработчике
	var $handler = ''; // site/handlers/{$handler}.php
	var $handler_full = false; // =/home/..../site/handlers/{$handler}.php

	//конструктор
	function BasicRequestHandler( $config_path = 'config/default.php' )
	{

		//пытаемся прочесть файл конфигурации
		if (is_object($config_path))
		{
			config_joinConfigs($this, $config_path);
		}
		else
		if(@is_readable($config_path)) 
		{
			require_once($config_path);
		}
		else
		{
			$uri  = preg_replace("/\?.*$/", "",$_SERVER["REQUEST_URI"]);
			$page = $_REQUEST["page"];
			$uri  = substr( $uri, 0, strlen($uri)-strlen($page) );
			$uri  = rtrim( $uri, "/" )."/setup";
			die("Cannot read local configurations. May be you should try to <a href='".$uri."'>run installer</a>, if any?");
		}

		//вычисляем base_url
		if( !isset($this->base_url) )
			$this->base_url = dirname($_SERVER["PHP_SELF"]).( dirname($_SERVER["PHP_SELF"])!='/' ? '/' : '' );
		if( !isset($this->base_dir) )
			$this->base_dir = $_SERVER["DOCUMENT_ROOT"].$this->base_url;
		if (!isset($this->host_url) )
			$this->host_url = strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0,
				strpos($_SERVER['SERVER_PROTOCOL'], '/')))
				. '://'.$_SERVER['SERVER_NAME'].
				($_SERVER['SERVER_PORT'] === '80' ? '' : ':'.$_SERVER['SERVER_PORT']);

		$this->_SetDomains();

		//избавляемся от квотов
		if (get_magic_quotes_gpc()){
			$this->_FuckQuotes($_POST);
			$this->_FuckQuotes($_GET);
			$this->_FuckQuotes($_COOKIE);
			$this->_FuckQuotes($_REQUEST);
		}

		$this->onAfterLoadConfig();

		//инициализируем базовые объекты
		if($this->enable_debug)
		{
			$this->useClass("Debug");
			Debug::init();
		}
		else
		{
			$this->useClass("DebugDummy");
		}
		
		Debug::trace("RH: creating DBAL");
		
		if ($this->db_al)
		{
			$this->UseClass("DBAL");
//			$this->db =& new DBAL( $this );
			$this->db =& DBAL::getInstance( $this );
			if($this->db_set_encoding)
			{
				$this->db->Query("SET NAMES ".$this->db_set_encoding);
			}
		}
		
		// ВЫКЛЮЧАЕМ tpl И msg если что
		if ($this->tpl_disable===true)
		{
			Debug::trace("RH: creating TPL : DISABLED");
		} else 
		{
			Debug::trace("RH: creating TPL");
			$this->UseClass("TemplateEngine");
			$this->tpl =& new TemplateEngine( $this );
			$this->tpl->set( '/', $this->base_url );
		}
		
		if ($this->msg_disable===true)
		{
			Debug::trace("RH: creating MSG : DISABLED");
		} else 
		{
			Debug::trace("RH: creating MSG");
			$this->UseClass("MessageSet");
			$this->msg =& new MessageSet( $this );
			$this->tpl->msg =& $this->msg;
		}
		Debug::trace("RH: constructor done");
	}

	function onAfterLoadConfig()
	{
	}

	// функция, заполняющая поля *_domain, чтобы помогать кукам и вообще всем
	function _SetDomains()
	{
		if (!isset($this->base_domain))
			$this->base_domain    = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
		if (!isset($this->current_domain))
			$this->current_domain = preg_replace("/^www\./i", "", $_SERVER["HTTP_HOST"]);
		if (!isset($this->cookie_domain))
			// lucky@npj: see http://ru.php.net/manual/ru/function.setcookie.php#49350
			$this->cookie_domain = strpos($this->base_domain, '.') === false   ? false : ".".$this->base_domain;

		session_set_cookie_params(0, "/", $this->cookie_domain); 
	}  

	//основная функция обработки запроса
	function Handle( $ri=false )
	{
		if($ri)
			$this->ri =& $ri;

		if (!isset($this->ri))
		{
			//инициализация $ri по умолчанию
			$this->UseScript('classes','RequestInfo');
			$this->ri =& new RequestInfo($this); // kuso@npj: default RI должен быть с одним параметром имхо
		}

		//$ri возвращает информацию о строке запроса "внутри сайта"
		//идея такая: http://www.mysite.ru/[$this->url]
		//zharik: не уверен, что ->url удачное имя переменной. Имхо, лучше ->site_url
		//kuso@npj: вообще говоря, мне он не нужен, я планировал пользоваться $ri->url.
		//          можно вообще забить на $rh->*url, если тебе не кажется это зачем-то нужным.
		//          а почему именно "site_" -- я не понял.
		$this->url = $this->ri->GetUrl();

		//инициализация принципала
		$this->InitPrincipal();
		//определение обработчика
		$this->MapHandler($this->url);

		//построение окружения
		$this->InitEnvironment();

		//выполнение обработчика
		$after_execute = $this->Execute();

		//пост-обработка запроса и возвращение результата
		return $this->PrepareResult( $after_execute );
	} 

	//Инициализация принципала.
	function &InitPrincipal()
	{

		$this->UseClass("Principal");
		$this->principal = &new Principal( $this, $this->principal_storage_model, 
			$this->principal_security_models );

		if ($this->principal->Identify() > PRINCIPAL_AUTH) 
		{
			$this->principal->Guest();
		}
		return $this->principal;

	}

	//Выбор обработчика на основе строки запроса и карты обработчиков.
	function MapHandler($url)
	{
		if( $url!='' )
		{
			$A = explode('/',rtrim($url,'/'));
			//ищем в карте сообщений
			//ищем самое длинное вхождение
			$s = '';
			foreach($A as $i=>$a)
			{
				$s .= ($s ? '/' : '').$a;
				if (isset($this->handlers_map[$s]))
				{
					$_handler = $this->handlers_map[$s];
					$this->handler = $_handler;
					$j = $i+1;
				}
			}
			//если нашли хоть что-то - заканчиваем
			if ( $this->handler ) return $this->_UrlTrail($A,$j);
			//ищем файлы на диске
			if ( $this->url_allow_direct_handling ) 
			{
				$s = '';
				foreach($A as $i=>$a)
				{
					$s .= ($s ? "/" : "").$a;
					if ( $this->handler_full = $this->FindScript("handlers",$s) )
						return $this->_UrlTrail($A,++$i);
				}
			}
		}
		//не нашли обработччик? пытаемся взять обработчик по умолчанию
		if($this->default_handler){
			$this->handler = $this->default_handler;
			return true;
		}
		//всё же не нашли обработчик? Запускаем 404.
		//Должен быть такой системынй обработчик
		$this->handler = '404';
		return true;
	}
	//формирует информацию об остатке адреса для обработчика
	function _UrlTrail(&$A,$i)
	{
		if( $i<count($A) )
		{
			$this->params = array_slice($A,$i);
			$this->params_string = implode('/',$this->params);
		}
		return true;
	}

	//Построение стандартного окружения.
	function InitEnvironment()
	{
		// на этом уровне включает только заполнение очень полезной
		// шаблонной переменной "/", соответствующей корню сайта
		$this->tpl->Set( "/", $this->ri->Href("") );
		$this->tpl->Set( "lib", $this->ri->Href($this->lib_href_part)."/" );
		$this->tpl->SetRef( "SITE", $this);
	}

	//Запуск выбранного обработчика на исполнение.

	function Execute( $handler='', $type="handlers" )
	{
		//так какой же обработчик брать?
		if( $handler ){
			//пользователь мог указать явно
			$this->handler_full = false;
			$this->handler = $handler;
		}
		if( !$this->handler_full )
			//обработчик могли взять из класс-мапа
			$this->handler_full = $this->FindScript_($type,$this->handler);

		//создаём алиасы для обработчика
		$rh =& $this;
		include( $this->FindScript("handlers","_enviroment") );

		//Запуск выбранного обработчика на исполнение.
		ob_start();
		$result = include( $this->handler_full );
		if ($result===false) 
		{
			throw new Exception("Problems (file: ".__FILE__.", line: ".__LINE__."): ".ob_get_contents());
		}
//		$this->debug->Error("Problems (file: ".__FILE__.", line: ".__LINE__."): ".ob_get_contents());
		if (($result===NULL) || ($result===1)) $result = ob_get_contents(); 
		// ===1 <--- подозрительно.
		ob_end_clean();

		return $result;
	}
	//Пост-обработка результатов работы.
	function PrepareResult( $after_execute )
	{
	 /*
	 На этом уровне проверяем, нужно ли оборачивать результат в html.html
	 Для дополнительной пост-обработки окружения перегружать этот метод в наследниках.
	  */
		$tpl =& $this->tpl;
		if( !$tpl->Is("HTML:html") )
		{
			if (!$tpl->Is("HTML:body")) $tpl->Set("HTML:body", $after_execute);
			return $tpl->Parse( "html.html" );
		}
		else
			return $tpl->get("HTML:html");
	}


	// Алиасы, специфичные для RH
	function UseClass( $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false, $hideExc = false )
	{
		$this->UseScript("classes",$name,$level,$dr,$ext,$withSubDirs,$hideExc);
	}

	function UseLib( $library_name, $file_name="" )
	{
		// library is near core, library have no levels
		//$direction = 0;
		// lucky@npj: фиг вам -- где угодно. сначала в приложении, затем в core
		$direction = 1;
		$level = 0; 
		// usually library have one file to link itself
		if ($file_name == "") $file_name = $library_name; 
		$ext="php";

		$this->UseScript( $this->lib_dir, $library_name."/".$file_name, $level, $direction, $ext);
	}

	function getPluralizeDir($classname)
	{
		$this->UseClass("Inflector");
		$words = preg_split('/[A-Z]/', $classname);
		$last_word = substr($classname, -strlen($words[count($words)-1])-1);
		$last_word = strtolower($last_word);
		return Inflector::pluralize($last_word);
	}
  
  /*
  МЕТОДЫ ЗАВЕРШЕНИЯ
	*/

	function End()
	{
	 /*
	 Штатное завершение работы.
	  */
	}

	function Redirect( $href )
	{
		if (strpos($href,"http://") !== 0) 
			$href = $this->ri->_host_prot.$href;

		header("Location: $href"); 
		exit;
	}

	function Error($msg)
	{
		echo '<hr>'.$msg;   
	}

  /*
  ВНУТРЕННИЕ МЕТОДЫ
	*/

	// удаляем "магические" квоты из предоставленного массива
	// и всех содержащихся в нём массивов
	function _FuckQuotes(&$a)
	{
		if(is_array($a))
			foreach($a as $k => $v)
				if(is_array($v)) $this->_FuckQuotes($a[$k]);
				else $a[$k] = stripslashes($v);
	}

}

?>
