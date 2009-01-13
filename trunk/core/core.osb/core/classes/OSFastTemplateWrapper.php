<?
  
/*
  
  OSFastTemplateWrapper -- Обёртка для OSFastTemplate, осуществляет связь с $rh.
    Используя переменные окружения $rh пытается подгузить функции и форматтеры по первому требованию.
  
  ------------------
  
  * OSFastTemplate ( $rh, $SIGNATURES = array() ) -- конструктор
    - $rh -- ссылка на $rh
    - $SIGNATURES -- дополнительный синтаксис (пока не используется)
  
  * GetAction( $func_name, $string, $PARAMS=array() ) -- аналогично OSFastTemplate,
              если не нашёл, то пытается загрузить из [actions], не нашел - цивилизованно умирает
              !! функция-экшн должна принимать ссылку на $rh и хэш параметров
              хэндлер должен записать результат в переменную $result
  
  * Error($str) -- отправляет сообщения об ошибке через $rh->debug
  
=============================================================== v.1 (Zharik)
*/
  
require_once( dirname(__FILE__)."/OSFastTemplate.php" );
  
class OSFastTemplateWrapper extends OSFastTemplate {
  
  var $rh;
  
  //конструктор
  function OSFastTemplateWrapper ( &$rh, $SIGNATURES = array() ) {
    
    //link $rh
    $this->rh =& $rh;
    
    OSFastTemplate::OSFastTemplate( $rh->DIRS["templates"][CURRENT_LEVEL], $rh->templates_cache_dir, $SIGNATURES );
    
    //всякие настройки от $rh
    $this->PRE_FILTERS =& $rh->PRE_FILTERS;
    $this->POST_FILTERS =& $rh->POST_FILTERS;
    
  }
  
  /*** ищем шаблоны по всем уровням движка ***/
  function _get_tpl_filename( $tpl_file ){
    return $this->rh->FindScript( "templates", $tpl_file, CURRENT_LEVEL, SEARCH_DOWN, true );
  }
  
  /*** Перекрываем функции проверки форматтеров и экшенов ***/
  
  function Action( $func_name, $string, $PARAMS=array() ){
    $_func_name = 'action_'.$func_name;
    //подготовка параметров
    if( is_array($string) ) $PARAMS =& $string;
    else $PARAMS['__string'] =& $string;
    //системные экшены
    if( method_exists( $this, $_func_name ) )
      return $this->$_func_name( $PARAMS );
    //проверка существования
    if( !isset($this->ACTIONS[$func_name]) ){
      //окружение
      $rh =& $this->rh;
      include( $rh->FindScript("scripts","page") );
      //стараемся загрузить форматтер
      @require_once( $this->rh->FindScript("actions",$func_name) );
      //проверяем наличие функции
      if( !function_exists($_func_name) ){
        //запоминаем как хэндлер
        $this->ACTIONS[$func_name] = array($func_name);
        return $result;
      }else
        //запоминаем как функцию
        $this->ACTIONS[$func_name] = $_func_name;
    }
    //запускаем на исполнение
    $action = $this->ACTIONS[$func_name];
    if( !is_array($action) ) return $action( $this->rh, $PARAMS );
    else{
      //окружение
      $rh =& $this->rh;
      include( $rh->FindScript("scripts","page") );
      include( $rh->FindScript("actions",$func_name) );
      //ожидаем от хэндлера $result
      return $result;
    }
  }
  
  function Trace($str){
    $this->rh->debug->Trace("OSFastTemplate: ".$str);
  }
  
  function Error($str){
    $this->rh->debug->Error("OSFastTemplate: ".$str);
  }
  
  //связь с ListObject
  function Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ){
    //проверяем, подгружен ли уже ListObject
    if(!$this->list){
      $this->rh->UseClass("ListObject");
      $this->list =& new ListObject( $this->rh, $ITEMS );
    }else
      $this->list->DATA =& $ITEMS;
    //парсим
    $this->list->implode = $implode;
    return $this->list->parse( $tpl_root, $store_to, $append );
  }

  /*** системные экшены ***/
  
  function action_include( &$PARAMS ){
    return $this->Parse( $PARAMS['tpl'] );
  }
  
  function action_dummy( &$PARAMS ){
    //экшн распорка проивольных размеров (из ракеты)
    $rh =& $this->rh;
    $tpl=& $this->rh->tpl;
    $params =& $PARAMS;
    include ($rh->FindScript('actions', '_dummy'));
    return $takeit;
    //return '<!-- --><img class="block" src="images/z.gif" width="1" height="1" align="top" alt="" border="0" /><!-- -->';
  }
  
}

?>