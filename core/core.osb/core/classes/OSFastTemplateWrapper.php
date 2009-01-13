<?
  
/*
  
  OSFastTemplateWrapper -- ������ ��� OSFastTemplate, ������������ ����� � $rh.
    ��������� ���������� ��������� $rh �������� ��������� ������� � ���������� �� ������� ����������.
  
  ------------------
  
  * OSFastTemplate ( $rh, $SIGNATURES = array() ) -- �����������
    - $rh -- ������ �� $rh
    - $SIGNATURES -- �������������� ��������� (���� �� ������������)
  
  * GetAction( $func_name, $string, $PARAMS=array() ) -- ���������� OSFastTemplate,
              ���� �� �����, �� �������� ��������� �� [actions], �� ����� - ������������� �������
              !! �������-���� ������ ��������� ������ �� $rh � ��� ����������
              ������� ������ �������� ��������� � ���������� $result
  
  * Error($str) -- ���������� ��������� �� ������ ����� $rh->debug
  
=============================================================== v.1 (Zharik)
*/
  
require_once( dirname(__FILE__)."/OSFastTemplate.php" );
  
class OSFastTemplateWrapper extends OSFastTemplate {
  
  var $rh;
  
  //�����������
  function OSFastTemplateWrapper ( &$rh, $SIGNATURES = array() ) {
    
    //link $rh
    $this->rh =& $rh;
    
    OSFastTemplate::OSFastTemplate( $rh->DIRS["templates"][CURRENT_LEVEL], $rh->templates_cache_dir, $SIGNATURES );
    
    //������ ��������� �� $rh
    $this->PRE_FILTERS =& $rh->PRE_FILTERS;
    $this->POST_FILTERS =& $rh->POST_FILTERS;
    
  }
  
  /*** ���� ������� �� ���� ������� ������ ***/
  function _get_tpl_filename( $tpl_file ){
    return $this->rh->FindScript( "templates", $tpl_file, CURRENT_LEVEL, SEARCH_DOWN, true );
  }
  
  /*** ����������� ������� �������� ����������� � ������� ***/
  
  function Action( $func_name, $string, $PARAMS=array() ){
    $_func_name = 'action_'.$func_name;
    //���������� ����������
    if( is_array($string) ) $PARAMS =& $string;
    else $PARAMS['__string'] =& $string;
    //��������� ������
    if( method_exists( $this, $_func_name ) )
      return $this->$_func_name( $PARAMS );
    //�������� �������������
    if( !isset($this->ACTIONS[$func_name]) ){
      //���������
      $rh =& $this->rh;
      include( $rh->FindScript("scripts","page") );
      //��������� ��������� ���������
      @require_once( $this->rh->FindScript("actions",$func_name) );
      //��������� ������� �������
      if( !function_exists($_func_name) ){
        //���������� ��� �������
        $this->ACTIONS[$func_name] = array($func_name);
        return $result;
      }else
        //���������� ��� �������
        $this->ACTIONS[$func_name] = $_func_name;
    }
    //��������� �� ����������
    $action = $this->ACTIONS[$func_name];
    if( !is_array($action) ) return $action( $this->rh, $PARAMS );
    else{
      //���������
      $rh =& $this->rh;
      include( $rh->FindScript("scripts","page") );
      include( $rh->FindScript("actions",$func_name) );
      //������� �� �������� $result
      return $result;
    }
  }
  
  function Trace($str){
    $this->rh->debug->Trace("OSFastTemplate: ".$str);
  }
  
  function Error($str){
    $this->rh->debug->Error("OSFastTemplate: ".$str);
  }
  
  //����� � ListObject
  function Loop( &$ITEMS, $tpl_root, $store_to='', $append=false, $implode=false ){
    //���������, ��������� �� ��� ListObject
    if(!$this->list){
      $this->rh->UseClass("ListObject");
      $this->list =& new ListObject( $this->rh, $ITEMS );
    }else
      $this->list->DATA =& $ITEMS;
    //������
    $this->list->implode = $implode;
    return $this->list->parse( $tpl_root, $store_to, $append );
  }

  /*** ��������� ������ ***/
  
  function action_include( &$PARAMS ){
    return $this->Parse( $PARAMS['tpl'] );
  }
  
  function action_dummy( &$PARAMS ){
    //���� �������� ����������� �������� (�� ������)
    $rh =& $this->rh;
    $tpl=& $this->rh->tpl;
    $params =& $PARAMS;
    include ($rh->FindScript('actions', '_dummy'));
    return $takeit;
    //return '<!-- --><img class="block" src="images/z.gif" width="1" height="1" align="top" alt="" border="0" /><!-- -->';
  }
  
}

?>