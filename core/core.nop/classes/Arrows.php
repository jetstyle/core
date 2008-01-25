<?php
/*
   Arrows (  &$rh, $from='', $where='', $page_size=0, $frame_size=0 )  -- Класс для орагнизации страничной прокрутки
      - $rh -- ссылка на $rh
      - $from, $where, $page_size, $frame_size -- см. Setup()
      Здесь же восстанавливается ->current_page из запроса
  
  ---------
  * Setup($form,$where='',$page_size=0,$frame_size=0) -- привязка таблицы и вычисление статистик
      - $from -- вхождение FROM для sql-запроса
      - $where -- вхождение WHERE для sql-запроса
      - $page_size -- число записей, выводимых на одной странице
      - $frame_size -- число закладок, выводимых на одной странице
  
  * Limit() -- возвращает максимальное число элементов для выборки
  
  * Offset() -- возвращает позицию, начиная с которой нужно выбирать записи
  
  * GetRecordCount() -- возвращает общее число записей из $from-$where
  
  * SetRecordCount($record_count) -- устанавливает общее число записей
      Если общее число записей установленно вручную, то класс не обращается к БД.
  
  * Parse( $template, $store_to='', $append=false ) -- формирует HTML-код листалки по заданным шаблонам
  
  * _Restore() -- восстанавливает состояние листалки:
    - читает $current_page из $_GET / $_POST
    - считает $record_count в $from-$where, если оно не задано заранее
  
  * _Calculate() -- раситывает вторичные статистики - число фреймов и прочее
  
=============================================================== v.3 (Zharik)
*/

class Arrows extends RequestInfo {
  
  var $rh;
  var $tpl;
  var $configured = 0;
  
  //текущая позиция
  var $current_page = -1; // 0-based
  //из какой переменной брать?
  var $varname = "_page";
  
  //arrows stats
  var $record_count = -1;
  var $page_count = 0;
  var $page_size = 10;
  var $frame_size = 10;
  
  //sql settings
  var $from = '';
  var $where = '';
  
  var $list_store_to = '_List'; //куда класть отрендерённый список для обёртки
  var $implode = false; //рендерить разделитель?
  
  function Arrows( &$rh, $from='', $where='', $page_size=10, $frame_size=10 )
  {
    RequestInfo::RequestInfo($rh);
    $this->tpl =& $rh->tpl;
    $this->load( $rh->ri );
    $this->url = $rh->ri->url;
    $this->set( $this->varname, $_GET["varname"] ); // узнаём страницу, if any
    if($from!='')
      $this->Setup( $from, $where, $page_size, $frame_size );
  }
  
  function Setup( $from, $where='', $page_size=10, $frame_size=10 )
  {
    $this->from = $from;
    if($where) $this->where = $where;
    if($page_size) $this->page_size = $page_size;
    if($frame_size) $this->frame_size = $frame_size;
  }
  
  function Limit()
  {
    $this->_Restore();
    return $this->page_size;
  }
  
  function Offset()
  {
    $this->_Restore();
    return $this->current_page * $this->page_size;
  }
  
  function GetRecordCount()
  {
    return $this->record_count;
  }
  
  function SetRecordCount( $record_count )
  {
    $this->record_count = $record_count;
    $this->_Calculate();
  }
  
  function _Restore()
  {
    //восстанавливаем индекс текущей страницы
    $this->current_page = (integer)$this->Get( $this->varname );
    
    //если $record_count уже задан, то ничего делать не нужно
    if( $this->record_count>=0 ) return;
    
    //считаем число записей в БД
    $r = $this->rh->db->QueryOne("SELECT count(*) as count FROM ".$this->from." WHERE ".$this->where);
    $this->record_count = $r['count'];
    $this->_Calculate();
  }
  
  function _Calculate()
  {
    $this->page_count = ceil( $this->record_count / $this->page_size );
  }
  
  function Parse( $template, $store_to='', $append=false )
  {
    $this->_Restore();
    
    Debug::trace('Arrows::Parse - $current_page='.$this->current_page.', $record_count='.$this->record_count);
    
    $tpl =& $this->tpl;
    
    //мелкие полезности
    $tpl->set( '_PageNo', $this->current_page+1 );
    $tpl->set( '_PageCount', $this->page_count );
    $tpl->set( '_RecordCount', $this->record_count );
    $tpl->set( '_PageSize', $this->page_size );
    
    //пусто?
    if( $this->page_count==1 )
    {
      $tpl->parse( $template.'_Empty', $store_to, $append );
      return;
    }
    
    $_count = floor($this->current_page / $this->frame_size);
    
    //prev
    if( $_count > 0 )
    {
      $tpl->set( '_HavePrev', true );
      $tpl->set(
        'Href_Prev', 
        $this->HrefPlus( $this->rh->url, array( $this->varname => $_count*$this->frame_size - 1 ) )
       );
    }
    //next
    if( ($_count + 1)*$this->frame_size < $this->page_count )
    {
      $tpl->set( '_HaveNext', true );
      $tpl->set( 
        'Href_Next', 
        $this->HrefPlus( $this->rh->url, array( $this->varname => ($_count + 1)*$this->frame_size) )
       );
    }
    
    //готовим фреймы
    $_count = (integer)( $this->current_page/$this->frame_size );
    $PAGES = array();
    for(
      $i=0;
      $i<$this->frame_size && ( $_count*$this->frame_size + $i ) < $this->page_count ;
      $i++
     ){
      $r['PageNo'] = $_count*$this->frame_size + $i + 1;
      $r['IsCurrent'] = ($_count*$this->frame_size + $i) == $this->current_page;
      $r['Href'] = $this->HrefPlus( $this->rh->url, array( $this->varname => $_count*$this->frame_size + $i ) );
      $PAGES[] = $r;
    }
    //рендерим фреймы
    $tpl->loop( $PAGES, $template.'_List', $this->list_store_to, false, $this->implode );
    
    //обёртка
    return  $tpl->parse( $template, $store_to, $append );
  }
}
?>