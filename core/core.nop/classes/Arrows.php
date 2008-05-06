<?php
/*
   Arrows (  &$rh, $from='', $where='', $page_size=0, $frame_size=0 )  -- ����� ��� ����������� ���������� ���������
      - $rh -- ������ �� $rh
      - $from, $where, $page_size, $frame_size -- ��. Setup()
      ����� �� ����������������� ->current_page �� �������
  
  ---------
  * Setup($form,$where='',$page_size=0,$frame_size=0) -- �������� ������� � ���������� ���������
      - $from -- ��������� FROM ��� sql-�������
      - $where -- ��������� WHERE ��� sql-�������
      - $page_size -- ����� �������, ��������� �� ����� ��������
      - $frame_size -- ����� ��������, ��������� �� ����� ��������
  
  * Limit() -- ���������� ������������ ����� ��������� ��� �������
  
  * Offset() -- ���������� �������, ������� � ������� ����� �������� ������
  
  * GetRecordCount() -- ���������� ����� ����� ������� �� $from-$where
  
  * SetRecordCount($record_count) -- ������������� ����� ����� �������
      ���� ����� ����� ������� ������������ �������, �� ����� �� ���������� � ��.
  
  * Parse( $template, $store_to='', $append=false ) -- ��������� HTML-��� �������� �� �������� ��������
  
  * _Restore() -- ��������������� ��������� ��������:
    - ������ $current_page �� $_GET / $_POST
    - ������� $record_count � $from-$where, ���� ��� �� ������ �������
  
  * _Calculate() -- ���������� ��������� ���������� - ����� ������� � ������
  
=============================================================== v.3 (Zharik)
*/

class Arrows extends RequestInfo {
  
  var $rh;
  var $tpl;
  var $configured = 0;
  
  //������� �������
  var $current_page = -1; // 0-based
  //�� ����� ���������� �����?
  var $varname = "_page";
  
  //arrows stats
  var $record_count = -1;
  var $page_count = 0;
  var $page_size = 10;
  var $frame_size = 10;
  
  //sql settings
  var $from = '';
  var $where = '';
  
  var $list_store_to = '_List'; //���� ������ ������������ ������ ��� ������
  var $implode = false; //��������� �����������?
  
  function Arrows( &$rh, $from='', $where='', $page_size=10, $frame_size=10 )
  {
    RequestInfo::RequestInfo($rh);
    $this->tpl =& $rh->tpl;
    $this->load( $rh->ri );
    $this->url = $rh->ri->url;
    $this->set( $this->varname, $_GET["varname"] ); // ����� ��������, if any
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
    //��������������� ������ ������� ��������
    $this->current_page = (integer)$this->Get( $this->varname );
    
    //���� $record_count ��� �����, �� ������ ������ �� �����
    if( $this->record_count>=0 ) return;
    
    //������� ����� ������� � ��
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
    
    //������ ����������
    $tpl->set( '_PageNo', $this->current_page+1 );
    $tpl->set( '_PageCount', $this->page_count );
    $tpl->set( '_RecordCount', $this->record_count );
    $tpl->set( '_PageSize', $this->page_size );
    
    //�����?
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
    
    //������� ������
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
    //�������� ������
    $tpl->loop( $PAGES, $template.'_List', $this->list_store_to, false, $this->implode );
    
    //������
    return  $tpl->parse( $template, $store_to, $append );
  }
}
?>