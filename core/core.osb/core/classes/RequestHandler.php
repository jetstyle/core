<?php
  
  require_once( dirname(__FILE__)."/ConfigProcessor.php" );
  
/*
    RequestHandler( $config_path="core/config.php"  )  -- конструктор
      - $config_path -- путь к конфигурационному файлу (присоединяет его в себя в конструкторе)
        читает конфиг, делает первичную инициализацию и запускает сценарий инициализации
  
  ---------
  
  * End() -- штатное завершение работы над запросом
  
  * EndError( $message="" ) -- штатное завершение работы над запросом + сообщение об ошибке
  
  * _FuckQuotes( $array ) -- рекурсивно убивает магические квоты
  
  * &GetVar( $name, $type="" ) -- возвращает переменную из пространства $this->GLOBALS
                                  типизирует её, если указан тип
  
  * ProceedRequest() -- простейшая обоработка запроса,
                        т.е. выполнение указанного хендлера
  
  * Output() -- отдаёт результат работы наружу, фактически echo 
  
  * UseClass( $name, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN ) -- через _FindScript($type="classes")
          ищет файл класса и инклюдит его
          семантика переменных как в _FindScript() (см. ConfigProcessor.php )
  
  * UseModule( $name, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN ) -- 
          через _FindScript($type="modules") ищет файл модуля, 
          инклюдит его и создаёт объекст, возвращает ссылку. 
          Использовать наследников так: $news =& $rh->UseModule("news");
          семантика переменных как в _FindScript() (см. ConfigProcessor.php )
  
  * Redirect( $url ) -- редирект на указанный адрес

  * HeadersNoCache() -- кидает заголовки, что бы браузеры не кэшировали контент
  
  var $GLOBALS - пространство глобальных переменных
  var $CLASSES - запоминаем загруженные классы, чтоб не шариться по диску
  var $LIBS - запоминаем загруженные библиотеки, чтоб не шариться по диску
  var $MODULES - запоминаем загруженные модули, чтоб не шариться по диску
  
=============================================================== v.3 (Zharik/Kuso)
*/
  
//класс
class RequestHandler extends ConfigProcessor {
  
  var $GLOBALS = array();
  var $CLASSES = array();
  var $LIBS = array();
  var $MODULES = array();
  
  function RequestHandler( $config_path="core/config.php" ) {

    //переменная для хэндлеров по умолчанию
    $this->page_var_name = "page";
    
    //избавляемся от квотов
    if (get_magic_quotes_gpc()){
      $this->_FuckQuotes($_POST);
      $this->_FuckQuotes($_GET);
      $this->_FuckQuotes($_COOKIE);
    }
    //пространство переменных
    $this->GLOBALS = array_merge( $this->GLOBALS, $_POST );
    $this->GLOBALS = array_merge( $this->GLOBALS, $_GET );
    $this->GLOBALS = array_merge( $this->GLOBALS, $_COOKIE );
    
	 //читаем базовый конфиг
	 if (is_object($config_path))
	 {
		 config_joinConfigs($this, $config_path);
	 }
	 elseif(@is_readable($config_path)) 
	 {
		 require_once($config_path);
	 }
	 else
	 {
		 die("Cannot read local configurations.");
	 }

    //инициализируем ConfigProcessor
    ConfigProcessor::ConfigProcessor();
    
    //константы путей
    global $_SERVER;
    $PHP_SELF = $_SERVER["PHP_SELF"];
    $dir_name = str_replace( "\\", "/", dirname($PHP_SELF) );
    $this->path_rel = $dir_name.( $dir_name!='/' ? '/' : '' );
    $this->path_full = $_SERVER["DOCUMENT_ROOT"].$this->path_rel;
    $this->host_name = preg_replace('/:.*/','',$_SERVER["HTTP_HOST"]);
    $this->url_ = "http://".$this->host_name;
    $this->url = $this->url_.$this->path_rel;
    if($this->path_rel!='/')
      $this->url_rel = str_replace( $this->path_rel, '', $_SERVER["REQUEST_URI"] );
    else
      $this->url_rel = substr($_SERVER["REQUEST_URI"],1);

    //ручками транслируем строку запроса в $rh->GLOBALS
    $A = explode( '?', $this->url_rel );
    parse_str( $A[1], $_GLOBALS );
    $this->_FuckQuotes($_GLOBALS);
    $this->GLOBALS = array_merge( $this->GLOBALS, $_GLOBALS );
    //подготавливаем путь
    $_path = $this->GLOBALS['path'] ? $this->GLOBALS['path'] : $A[0];
    
    //сценарий инициализации
    include($this->FindScript("scripts","startup"));
    $this->_close_php = $this->FindScript("scripts","close");
    
    //translate query
    if( $this->path_class ){
      //зпускаем обработчик
      $this->UseClass( $this->path_class );
      eval('$this->path =& new '.$this->path_class.'($this);');
      $this->path->Handle( $_path );
    }
  }

  /**
   * singleton pattern
   */
  function &singleton($config_path = null)
  {
    static $instance = null;
    if ($instance != null)
      return $instance;
    ($config_path != null) ? $instance = new RequestHandler($config_path) : $instance = new RequestHandler();
    return $instance;
  }
  
  function End($str='',$OK=true){
//    if($OK)
//      header("HTTP/1.1 200 OK");
    //сценарий завершения
    @include($this->_close_php);
    die($str);
  }
  
  function EndError($str=""){
    if( isset($this->debug) && is_a( $this->debug, "Debug") )
      $this->debug->Error($str);
    else{
      echo "<font color='red'>".$str."</font>";
      $this->End();
    }
  }

  function &GetVar($name,$type=""){
    if($type!=""){
      $tt = $this->GLOBALS[ $name ];
      @settype($tt,$type);
      return $tt;
    }else return $this->GLOBALS[ $name ];
  }
  
  function _FuckQuotes(&$a){
   if(is_array($a))
    foreach($a as $k => $v)
     if(is_array($v)) $this->_FuckQuotes($a[$k]);
                 else $a[$k] = stripslashes($v);
  }

  function ProceedRequest()
  {
    $this->page = $this->GetVar($this->page_var_name);
    
    //analize handler
    $this->page = str_replace(".","",$this->page);
    $this->page = str_replace("/home/","",$this->page);
    if( $this->page[0]=="_" ) $this->EndError("Handler <b>".$this->page."</b> is not permitted.");
    $this->missed_OK = true;
    $fname = $this->FindScript("handlers",$this->page);
    if( !$fname ){
      $this->debug->Trace("Handler <b>".$this->page."</b> not found, get default <b>".$this->default_page."</b>.");
      $this->page = $this->default_page;
      $fname = $this->FindScript("handlers",$this->page);
    }
    if( !$fname ) $this->EndError("Cannot read default page <b>".$this->default_page."</b>");
    $this->missed_OK = false;
    
    //bind enviroment
//    $this->state->Set($this->page_var_name, $this->page);
    $this->GLOBALS[ $this->page_var_name ] = $this->page;
    $this->state->Set('page',$this->page);
    
    //bind enviroment script
    $rh =& $this;
    include($this->FindScript("scripts","page"));
   
    //execute handler
    include($fname);
  }

  /**
   * Handler - запускает на отработку указанный хэндлер
   *
   * @param   string    имя хэндлера
   * @param   array     необязательный параметр. массив переменных, передаваемый по ссылке, который будет развернут в пространство имен хэндлера
   * @access  public
   * @return  mixed     любое значение, которое хэндлер возвращает через return
   */  
  function Handler($handler)
  {
    if (func_num_args() == 2 && $vars =& func_get_arg(1) && is_array($vars))
      extract($vars, EXTR_OVERWRITE + EXTR_REFS);
    $rh =& $this;
    require $this->FindScript("scripts","page");
    return include($this->FindScript('handlers', $handler));
  }

  function UseClass( $name, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN )
  {   

    if( !$this->CLASSES[$name] )
    {
      $fname = $this->FindScript( "classes", $name, $level, $direction );
      require_once( $fname );
      $this->CLASSES[$name] = true;
    }
  }
  
  function UseLib( $name, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN ){
    if( !$this->LIBS[$name] ){
      $fname = $this->FindScript( "libs", $name, $level, $direction );
      require_once( $fname );
      $this->LIBS[$name] = true;
    }
  } 
  
  function &UseModule( $name, $new_instance=false, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN ){
    
    $class_name = "Module_".$name;
    
    //уже загружали такой модуль?
    if( isset($this->MODULES[$name]) ){
      if( $new_instance ){
        eval("\$obj =& new ".$class_name."(\$this);");
        return $obj;
      }else return $this->MODULES[$name];
    }
    
    //ищем файл
    $fname = $this->FindScript( "modules", $name, $level, $direction );
    require_once($fname);
    
    //проверяем наличие класса
    if(!class_exists($class_name))
      $this->EndError("Class for module <b>$name</b> is not found.");
    
    //создаём объект нужного класса
    eval("\$this->MODULES[ \$name ] =& new ".$class_name."(\$this);");
    $obj =& $this->MODULES[$name];
    $obj->InitInstance();
    
    //basic initialisation
    
    return $obj;
  }
  
  function Redirect($url){
    if( $this->GetVar('no_response') ){
//      header('HTTP/1.1 204 No Content');
      echo '';
    }else{
      $url = str_replace('&amp;','&', trim($url) );
      header("Location: ". $url );
    }
    $this->End('',false);
  }
  
  function HeadersNoCache(){
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");             // Date in the past
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
    header("Cache-Control: no-cache");           // HTTP/1.1
    header("Pragma: no-cache");                                   // HTTP/1.0
  }
  
// EOC{ RequestHandler } 
}

?>
