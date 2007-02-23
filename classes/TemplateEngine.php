<?php
/*
  Шаблонный движок
  * прекомпиляция шаблонов в PHP
  * php actions, conditions, includes

  TemplateEngine( &$rh )

  ---------

  // Работа с доменом переменных

  * Get( $key )            -- получить значение (намеренно без ссылки)
  * Set( $key, $value=1 )  -- установить значение ключу
  * SetRef( $key, &$ref )  -- установить значение ссылкой
  * Append( $key, $value ) -- дописать в конец 
  * Is( $key )             -- true, если этот ключ хоть что-то значил (isset is a keyword)
  * Free( $key="" )        -- очистка домена или unset переменной
      - $key   -- имя переменной (поля), если пустое, то очищается весь домен
  * Load( $domain )        -- загрузка массива в ключи

  // Парсинг шаблонов

  * Parse( $tpl_name, $store_to="", $append=0, $dummy="" ) -- парсинг шаблона и возвращение результата. 
                                                              Возвращает строку-результат
      - $tpl_name    -- имя шаблонки, например "front.html:Menu" или "front.html"
      - $store_to    -- если установлено, то результат также сохраняется в переменную домена с таким именем
      - $append      -- если непустое $store_to, то результат не стирает значение переменной, а дописывается в конец
      - $dummy       -- на что заменять те переменные, которых в домене не окажется

  * ParseInstant( $template_content ) -- "отпарсить" контент, данный как параметр (а не брать из файла)

  * _SpawnCompiler() -- порождает компиляторскую часть

  * _FindTemplate( $tpl_filename, $level=false, $direction=-1 ) -- возвращает уровень и полный путь к файлу,
                                                                   в котором лежит кэш этого (под)шаблона
   
  // Шкуры

  * Skin( $skin_name="" )   -- переключить систему в другую шкуру (увеличив глубину стека)
  * UnSkin()                -- вернуться к предыдущей шкуре
  * _SetSkin( $skin_name )  -- для внутреннего пользования, устанавливает типовые переменные

  // Языковые конструкты шаблонного движка

  - Action( $action_name, &$params, $level=false, $direction=-1 ) -- вызов действия
      - $action_name -- lowered case названия действия
      - $level       -- с какого уровня дерева "actions" искать.
                        если не указано, то сначала ищется в самой свежей шкуре, потом 
                        вниз по стеку шкур, потом до самого дна -- "ядра"
      - $direction   -- направление поиска, по умолчанию: от края к ядру

  // Частоиспользуемые actions, которые имеет смысл вшить прямо сюда:
  * action_ActionName -- "системные" акшны, вшитые прямо в движок для скорости

  * _Message( $tag )
  * _Text   ( $tag )

  // развитие (ForR2, ???)
  ? _Connect( $what )             
  ? _Inline ( $what, $as = "js" ) 
  ? _Link   ( $href, $text="", $title = "")

  // Работа со списками (парсинг)

  * Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ) -- парсинг списка по коллекции шаблонов
       - $ITEMS             -- массив, который надо превратить в список (через *-оператор)
       - $tpl_root          -- префикс, с которого начинаются подшаблоны списка.
       - $store_to, $append -- аналогично TE::Parse
       - $implode           -- "склеивать" ли результат через подшаблон "разделитель"

    Пример коллекции шаблонов списка в файле "test.html" (в качестве $tpl_root="test.html:List"):
      {{TPL:List}}  ... {{TPL:List_Item}}one item{{/TPL:List_Item}} ... {{/TPL:LIST}}
      {{TPL:List_Empty}} если список пуст {{/TPL:List_Empty}}
      {{TPL:List_Separator}} | {{/TPL:List_Separator}}
    см. также класс ListObject

  // Доп. параметры

  * $this->msg -- может быть объект класса MessageSet


  ---------

  Необходимые параметры в $rh:

  $rh->tpl_markup_level  = TPL_MODE_CLEAN;
  $rh->tpl_compile       = TPL_COMPILE_SMART;
  $rh->tpl_root_dir      = "themes/";  // or "../" or "" -- где лежат шкуры
  $rh->tpl_root_href     = "/themes/"; // or "/"         -- как добраться по URL до них
  $rh->tpl_skin          = ""; // for no-skin-mode
  $rh->tpl_skin_dirs     = array( "css", "js", "images" ); // -- какие каталоги типовые

  $rh->tpl_action_prefix      = "rockette_action_";
  $rh->tpl_template_prefix    = "rockette_template_";
  $rh->tpl_template_sepfix    = "__";
  $rh->tpl_action_file_prefix   = "@@"; 
  $rh->tpl_template_file_prefix = "@";

  $rh->cache_dir              = "../_zcache/"; // or "_zcache/" -- куда сложен кэш

  ---------

  NB: все шаблоны должны быть .html -- это ускоряет парсинг


=============================================================== v.2 (kuso@npj, zharik@npj)
*/
define ("TPL_APPEND", 1);
define ("TPL_MODE_CLEAN",    0);
define ("TPL_MODE_COMMENTS", 1);
define ("TPL_MODE_TEXT",     2);
define ("TPL_COMPILE_NEVER",  0);
define ("TPL_COMPILE_SMART",  1);
define ("TPL_COMPILE_ALWAYS", 2);

class TemplateEngine extends ConfigProcessor
{
  var $rh;        // use: $this->rh->debug->Trace (..->Error)
  var $msg = false;
  var $domain;

  var $CONNECT = array();

  function TemplateEngine( &$rh )
  {
    $this->domain = array();
    $this->rh     = &$rh;
    if ( $rh->debug )
      $this->debug =& $rh->debug;

    // изначальный стек шкур на основе стека RH
    $this->DIRS = $rh->DIRS;

    // выбрать шкуру
    $this->Skin( $rh->tpl_skin );

    // настроить конфигуратор
    $this->skin_names = array(); // имена шкур
  }

  // Работа с доменом переменных -------------------------------------------------------------

  function Get( $key ) // -- получить значение (намеренно без ссылки)
  { return isset($this->domain[$key]) ? $this->domain[$key] : "" ; }

  function Set( $key, $value=1 )  // -- установить значение ключу
  { $this->domain[$key] = $value; }

  function SetRef( $key, &$ref )  // -- установить значение ссылкой
  { $this->domain[$key] = &$ref; }

  function Append( $key, $value ) // -- дописать в конец 
  { $this->domain[$key] .= $value; }

  function Is( $key ) // -- true, если этот ключ хоть что-то значил
  { return isset( $this->domain[$key] ); }

  function Free( $key="" ) // -- очистка домена или unset переменной
  { if ($key === "") $this->domain = array();
    else if( is_array($key) )
    {
      foreach($key as $k)
      unset( $this->domain[$k] );
    } else unset( $this->domain[$key] );
  }

  function Load( $domain ) // -- загрузка массива в ключи
  {
    foreach($domain as $k=>$v)
    {
      $this->Set( $k, $v );
    }
  }

  // Шкуры ------------------------------------------------------------------------------------

  function Skin( $skin_name="" ) // -- переключить систему в другую шкуру (увеличив глубину стека)
  {
    // запомнить каталог для FindScript
    $dir = $this->rh->tpl_root_dir.$skin_name;
    if ($skin_name != "") $dir.="/";
    $this->DIRS[] = $dir;
    // запомнить имя шкуры
    $this->skin_names[] = $skin_name;
    // установить шкуру
    return $this->_SetSkin( $skin_name );
  }

  function UnSkin() // -- вернуться к предыдущей шкуре
  {
    array_pop( $this->DIRS );
    array_pop( $this->skin_names );
    return $this->_SetSkin( $this->skin_names[ sizeof($this->DIRS)-1 ] );
  }

  function _SetSkin( $skin_name ) // -- для внутреннего пользования
  {
    $this->Set( "skin", $this->rh->tpl_root_href.$skin_name );
    foreach($this->rh->tpl_skin_dirs as $k=>$dir)
      $this->Set( $dir, $this->rh->tpl_root_href.$skin_name."/".$dir."/" );
    $this->_skin = $skin_name;
  }

  // Парсинг шаблонов --------------------------------------------------------------------------

  function _SpawnCompiler() // -- порождает компиляторскую часть
  {
    if (!isset($this->compiler))
    {
      require_once( dirname(__FILE__)."/TemplateEngineCompiler.php" );
      $this->compiler =& new TemplateEngineCompiler( $this );
    }
  }

  function _FindTemplate( $tpl_filename ) // -- возвращает уровень и полный путь к кэш-файлу
  {
    // 2. launch parent
    return ConfigProcessor::FindScript( "templates", $tpl_filename, false, -1, "html" );
  }

  function ParseInstant( $template_content ) // -- "отпарсить" контент, данный как параметр (а не брать из файла)
  {
    // 1. нам понадобится компилятор!
    $this->_SpawnCompiler();
    // 2. откомпилировать контент
    return $this->compiler->_TemplateCompile( $template_content, true ); // instant=true
  }

  function Parse( $tpl_name, $store_to="", $append=0, $dummy="" ) // -- парсинг шаблона и возвращение результата. 
  { 
  	  
    // 1. split tplname by :
    $a = explode( ":", $tpl_name );
    $name0 = $a[0]; // имя файла
    if (sizeof($a) > 1) $_name = $a[1]; // имя подшаблона
    else                $_name = "";
    // имя до расширения, возможно указывать имя шаблона без расширения
    $_pos = strrpos($tpl_name, ".");
    $name = $_pos ? substr($name0, 0, $_pos) : $name0; 
    // имя готовое для кэширования
    $tname = str_replace("/",$this->rh->tpl_template_sepfix, $name); 

    $this->rh->debug->Trace("Parsing: ".$tpl_name);

    // ????? kuso@npj: здесь уместно проверить, нет ли у нас уже такой функции.
    //       надо бы написать тест-кейс для этого

    // 2. получение имён файлов исходника и компилята
    $file_cached = $this->rh->cache_dir.
                   $this->_skin.
                   $this->rh->tpl_template_file_prefix.
                   //судя по названию, здесь должна использоваться tpl_template_file_prefix вместо tpl_cache_prefix
                   //$this->rh->tpl_cache_prefix.
                   $tname.".php";
    
    
    $this->rh->debug->Trace("Should be cached as: ".$file_cached);

    // 3. проверка наличия в кэше/необходимости рекомпиляции
    $recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
    $recompile = $recompile || !file_exists( $file_cached );
    if ($recompile)
    {
      $file_source = $this->_FindTemplate( $name );
                                                       
      $this->rh->debug->Trace( "source:".$file_source ."($name)" );
      $this->rh->debug->Trace( "cache to:".$file_cached. "($tname)" );

      if ($file_source && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS))
        if (@filemtime($file_cached) >= @filemtime($file_source)) $recompile = false;
    }
    // 4. перекомпиляция
    if ($recompile) 
    { 
      $this->_SpawnCompiler();
      $this->compiler->TemplateCompile( $this->_skin, $tname, $file_source, $file_cached );
    }
    // 5. парсинг-таки
    
    include_once( $file_cached );

    $func_name = $this->rh->tpl_template_prefix.$this->_skin.
                    $this->rh->tpl_template_sepfix.$tname.
                 $this->rh->tpl_template_sepfix.$_name;

    if (function_exists ($func_name)) { // ru@jetstyle
      ob_start();
      $func_name($this);
      $res = trim(ob_get_contents());
      ob_end_clean();
    } else {
      //$this->rh->debug->Error( "Subtemplate ".$tpl_name." is not exists" );      
      return false;
    }
    
    //6. $dummy
    if( $res=='' ) $res = $dummy;
    
    $res = preg_replace("/<sup.\/>/", "", $res);

    
    //7. $store_to & $append
    if( $store_to )
      if( $append )
        $this->domain[ $store_to ] .= $res;
      else
        $this->domain[ $store_to ] = $res;
    
    return $res;
  }

  // Языковые конструкты шаблонного движка (Actions) -----------------------------------------------

  //zharik
  function Action( $action_name, &$params, $level=false, $direction=-1 ) // -- вызов действия
  {
    // by kuso@npj, 16-09-2004
    $action_name_for_cache = str_replace("/", "__", $action_name);

    //проверяем - а не системный ли это экшен?
    $func_name = 'action_'.$action_name_for_cache;
    
    if( method_exists( $this, $func_name) )
      return $this->$func_name( $params );

    //генерируем имя функции
    $func_name = $this->rh->tpl_action_prefix.$this->_skin.
                 $this->rh->tpl_template_sepfix.$action_name_for_cache;
    
    //проверяем экшен на существование
    if( !function_exists($func_name) )
    {
      //получение имён файлов для исходника и компилята
      $file_cached = $this->rh->cache_dir.
                     $this->rh->tpl_action_file_prefix.
                     $this->_skin.$this->rh->tpl_template_sepfix.
                     $action_name_for_cache.".php";
      
      //проверка на необходимость компиляции
      $recompile = $this->rh->tpl_compile != TPL_COMPILE_NEVER;
      $recompile = $recompile || !file_exists( $file_cached );
      if ($recompile)
      {
        $file_source = $this->FindScript_( "plugins", $action_name, $level, $direction );
        
        $this->rh->debug->Trace( $file_source );
        $this->rh->debug->Trace( $file_cached );
        
        if ($file_source && ($this->rh->tpl_compile != TPL_COMPILE_ALWAYS))
          if (@filemtime($file_cached) >= @filemtime($file_source)) $recompile = false;
      }
      
      //откомпилировать функцию
      if ($recompile) 
      {
        $this->_SpawnCompiler();
        $this->compiler->ActionCompile( $this->_skin, $action_name_for_cache, $file_source, $file_cached );
      }
      
      //подключить функцию
      include_once( $file_cached );
    }
    
    //выполняем и возвращаем результат
    ob_start();
    echo $func_name( $this, $params );
    $_ = trim(ob_get_contents());
    ob_end_clean();
    return $_;
  }
  
  // Aliases

  function _Message( $tag )
  {
    if ($this->msg) return $this->msg->Get( $tag );
    else return $tag;
  }
  function _Text( $tag )
  {
    if ($this->rh->db) $this->rh->debug->Error("Rockette::_Text -> Db not implemented");
    else return $this->_Message( $tag );
  }


  // Системные плугины

  function action_include( &$params ){
    return $this->Parse($params[0]);
  }
  
  function action_message( &$params ){
    $msgid = isset($params["_"]) && $params["_"] ? $params["_"] : $params[0];
    if ($this->msg) return $this->msg->Get( $msgid );
    else return $msgid;
  }
  function action_text( &$params ){
    $msgid = $params["_"] ? $params["_"] : $params[0];
    if ($this->rh->db) $this->rh->debug->Error("Rockette::action_Text -> Db not implemented");
    else return $this->_Message( $msgid );
  }
  
  //связь с ListObject
  function Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ){
    //проверяем, подгружен ли уже ListObject
    if(!(isset($this->list) && $this->list)){
      $this->rh->UseClass('ListObject');
      $this->list =& new ListObject( $this->rh, $ITEMS );
    }else
      $this->list->Set($ITEMS);
    //парсим
    $this->list->implode = $implode;
    return $this->list->parse( $tpl_root, $store_to, $append );
  }
  
// EOC{ TemplateEngine } 
}


?>