<?php
/*

Абстракция от метода реализации ЧПУ:
* восстановление урла из разных реализаций ЧПУ
* формирование хрефов для разных реализаций ЧПУ
* persistent querystring & form generation

RequestInfo( &$rh )

-------------------

// Работа с урлом

* GetUrl()
- без параметров, полагается на $_REQUEST, $_SERVER
- возвращает строку-относительный URL, соответствующий текущему запросу
- также инициализирует состояние по входным данным
- также заполняет все параметры для работы с урлом

* Href( $url, $ignore_state=STATE_IGNORE )
- генерирует абсолютный URL по относительному
- $state_ignore -- использует или нет "текущее состояние"

* _Href_Absolute( $url, $state="" ) -- для внутреннего пользования

* Form( $action, $method=METHOD_GET, $form_bonus="", $ignore_state=STATE_USE )
- генерирует <form action=...> по относительному action
- $form_bonus   -- дописывается внутрь тага <form>
- $state_ignore -- использует или нет "текущее состояние", заворачивая его в <input type=hidden...>

// Работа с ключами

* Set( $key, $value, $weak=0 ) -- установить поле в значение
- $key   -- имя поля (case-sensitive)
- $value -- устанавливаемое значение
- $weak  -- если установить в единицу, то не будет перезаписывать существующие поля

* SetRef( $key, &$value, $weak=0 ) -- установить поле ссылкой

* &Get( $key ) -- получить значение поля
- $key   -- имя поля (case-sensitive)

* Free( $key=NULL ) -- очистить поле/набор
- $key -- если пропущено, то очищает весь набор, иначе только соответствующее поле

// Расширенная работа с урлом

* HrefPlus( $url, $key, $value=1 ) -- генерировать URL, добавив одно поле (не сохраняет поле в наборе)
- $url   -- относительный URL, как в Href
- $key   -- имя поля (case-sensitive)
- $value -- устанавливаемое значение
- NB: $key может быть хэшем рода { $k=>$v }, тогда $value не нужно

* HrefMinus( $url, $key, $_bonus="" ) -- генерировать URL, игнорируя одно поле или массив
- $url    -- относительный URL, как в Href
- $key    -- имя поля (case-sensitive) из набора, которое игнорировать
- $_bonus -- вынутренний параметр, используется при вызове из HrefMinus

* _HrefMinusArray( $url, $key, $_bonus="" ) -- для внутреннего пользования. Не вызывать!



// Расширенная работа с объектом

* &Copy() -- вернуть RI копию данного

* Load( $keyset, $skip_char="_", $weak=0 ) -- загрузить поля из другого набора либо массива
- $keyset    -- хэш-массив или RI
- $skip_char -- пропускать поля, начинающиеся с этого СИМВОЛА
- $weak      -- если установить в единицу, то не будет перезаписывать существующие поля

* _Pack( $method=METHOD_GET, $bonus="", $only="" ) -- для внутреннего пользования
- упаковать в строку для GET/POST запроса
- $method -- вид строки "?key=value&key=value" или "<input type=hidden..."
- $bonus  -- дописывает в конце строки. важно для METHOD_GET, потому что может быть "?key=value&bonus", а может "?bonus"
- $only   -- опциональный префикс. если указан, то пакуются только те поля набора, которые начинаются с only

================================================================== v.1 (kuso@npj)
*/
define( "STATE_USE",    0 );
define( "STATE_IGNORE", 1 );
define( "METHOD_GET",    "get" );
define( "METHOD_POST",   "post" );

class RequestInfo
{
	protected $q = "?"; // параметры для корректного формирования урлов
	protected $s = "&";

	protected $url             = "";             // относительный URL, которым проинициализирован RI через GetUrl
	protected $values          = array();        // установленные параметры состояния
	protected $_compiled       = array("","");   // подготовленные get-post строки состояния
	protected $_compiled_ready = false;          // флаг соответствия _compiled == values

	protected $href_absolute   = false;          // if true, converts Href() result to "http://www.site.ru/..."
	// параметры для абсолютизации хрефов (с примером формата значения):
	public $_host      = "www.pixel-apes.com";                              // хост, с которого работаем
	public $_host_prot = "http://www.pixel-apes.com/";                      // хост с указанием протокола
	public $_base_url  = "something/like/this/";                            // урл до сайта (из конфига берётся)
	public $_base_full = "http://www.pixel-apes.com/something/like/this/";  // полностью собранный абсолютный урл

	public function __construct( &$rh ) // -- конструктор ничего не делает =)
	{
		$this->rh = &$rh;
		 
		// 0. заполнить параметры для хрефов
		$this->_host = preg_replace('/:.*/','',$_SERVER["HTTP_HOST"]);
		$this->_host_prot = "http://".$_SERVER["HTTP_HOST"];
		$this->_base_full = "http://".$this->_host.$this->rh->base_url;
		$this->_base_url  = $this->rh->base_url;

	}

	// Работа с урлом -----------------------------------------------------
	// v.0:  + mod_rewrite
	//       - 404
	//       - request_info
	//       - plain vanilla
	//
	// для поддержки методов работы править надо GetUrl и Form.

	public function getUrl() // -- возвращает строку-относительный URL, соответствующий текущему запросу
	{
		// RSS migrated ---
		if (isset($this->rh->rss)) $this->url = $this->rss->url;
		// ---- /rss

		// 1. получить $url from ["page"]
		$this->url = $_REQUEST["page"];

		// 2. считать состояние
		$this->Load( $_GET , "_" );    // GET  first
		//$this->Load( $_POST, "_" );    // POST second
		$this->Free("page");       // free "page", from where we receive nisht.

		return $this->url;
	}

	public function href( $url, $ignore_state=STATE_IGNORE ) // -- генерирует абсолютный URL по относительному
	{
		if ($ignore_state == STATE_USE) 
		{
			$state = $this->_pack();
		}
		else 
		{
			$state = "";
		}
		
		return $this->_href_Absolute( $url, $state );
	}

	// (внутренняя)
	protected function _href_Absolute( $url, $state="" ) // -- по готовому состоянию и урлу генерирует абсолютный URL
	{
		if (strpos($url, "http:") === 0) 
		{
			$prefix = "";
		}
		else
		{
			if ($this->href_absolute) 
			{
				$prefix = $this->_base_full;
			}
			else
			{
				$prefix = $this->_base_url;
			}
		}

		return $prefix.$url.$state;
	}

	public function form( $action, $method=METHOD_GET, $form_bonus="", $ignore_state=STATE_USE ) // -- генерирует <form..
	{
		if ($ignore_state == STATE_USE) $state = $this->_Pack(METHOD_POST);
		else                            $state = "";

		// mod_rewrite-only
		$_action = $this->Href( $action, STATE_IGNORE );

		return "<form action=\"".$_action."\" method=\"".$method."\" ".$form_bonus.">".$state;
	}

	// Работа с ключами ---------------------------------------------------

	public function set( $key, $value, $weak=0 ) // -- установить поле в значение
	{
		if ($weak) if (isset($this->values[$key])) return false;
		$this->_compiled_ready = 0;
		$this->values[$key] = $value;
		return true;
	}

	public function setRef( $key, &$value, $weak=0 ) // -- установить поле ссылкой
	{
		if ($weak) if (isset($this->values[$key])) return false;
		$this->_compiled_ready = 0;
		$this->values[$key] = &$value;
		return true;
	}

	public function &get( $key ) // -- получить значение поля
	{
		return $this->values[$key];
	}

	public function free( $key=NULL ) // -- очистить поле/набор
	{
		if ($key)
		if(is_array($key))
		{
			$kc = count($key);
			for($i=0; $i<kc; $i++) unset($this->values[$key[$i]]);
		}
		else unset($this->values[$key]);
		else $this->values = array();
		$this->_compiled_ready = 0;
	}

	// Расширенная работа с урлом --------------------------------------------

	public function hrefPlus( $url, $key, $value=1 )  // -- генерировать URL, добавив одно поле (не сохраняет поле в наборе)
	{
		if ($url === "") $url = $this->url;
		// вручную сделать параметр key=value
		if (is_array($key))
		{
			foreach($key as $k=>$v)
			{
				if($v!='')
				{
					$bonus .= $k."=".urlencode($v)."&";
				}
			}
			return $this->_hrefMinusArray( $url, $key, rtrim($bonus,'&') );
		}
		else
		{
			if($value!='')
			{
				$bonus = $key."=".urlencode($value);
			}
			// вызвать минус, который уберёт этот параметр из урла, если таковой был
			return $this->hrefMinus( $url, $key, $bonus );
		}
	}

	public function hrefMinus( $url, $key, $_bonus="" ) // -- генерировать URL, игнорируя одно поле
	{
		if ($url === "") $url = $this->url;
		if (is_array($key))
		{
			$key = array_flip($key);
			return $this->_HrefMinusArray( $url, $key, $_bonus );
		}
		$data = "";
		$f=0;
		foreach($this->values as $k=>$v)
		if ($k != $key && $v!='' )
		{
			if ($f) $data.=$this->s; else $f=1;
			$data .= $k."=".urlencode($v);
		}
		if ($_bonus != "")
		if ($data != "") $data= $this->q . $data . $this->s . $_bonus;
		else $data = $this->q. $_bonus;
		else  $data = $this->q. $data;

		return $this->_Href_Absolute( $url, $data );
	}

	public function _hrefMinusArray( $url, $key, $_bonus="" ) // -- для внутреннего использования. Не вызывать!
	{
		$data = "";
		$f=0;
		foreach($this->values as $k=>$v)
		if (!isset($key[$k]) && $v!='')
		{
			if ($f) $data.=$this->s; else $f=1;
			if(!is_array($v))
			{
				$data .= $k."=".urlencode($v);
			}
		}
		if ($_bonus != "")
		if ($data != "") $data= $this->q . $data . $this->s . $_bonus;
		else $data = $this->q. $_bonus;
		else  $data = $this->q. $data;

		return $this->_Href_Absolute( $url, $data );
	}

	// Расширенная работа с объектом -----------------------------------------

	protected function load( $keyset, $skip_char="_", $weak=0 ) // -- загрузить поля из другого набора либо массива
	{
		if (is_object($keyset)) $data = &$keyset->values;
		else $data = &$keyset;
		foreach ($data as $k=>$v)
		if ( (($skip_char == "") || ($k[0] != $skip_char)) && (($weak==0) || (!isset($this->values[$k]))) )
		$this->values[$k] = $v;
		$ready = 0;
	}

	protected function _pack( $method=METHOD_GET, $bonus="", $only="" ) // -- упаковать в строку для GET/POST запроса
	{
		if (!$this->_compiled_ready)
		{
			$this->_compiled[METHOD_GET ] = "";
			$this->_compiled[METHOD_POST] = "";

			$f=0;
			foreach($this->values as $k=>$v)
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
				 
				if ($f) $this->_compiled[METHOD_GET ].=$this->s; else $f=1;
				$this->_compiled[METHOD_GET ] .= $k."=".$v1;
				$this->_compiled[METHOD_POST] .= "<input type='hidden' name='".$k."' value='".$v0."' />\n";
			}
			$this->_compiled_ready = 1;
		}
		$data = $this->_compiled[$method];
		if ($method == METHOD_POST) return $data.$bonus;

		if ($bonus != "")
		if ($data != "") $data=$this->q.$data.$this->s.$bonus;
		else $data.=$this->q.$bonus;
		else if ($data != "") $data = $this->q.$data;
		 
		return $data;
	}


	// EOC{ RequestInfo }
}


?>