<?
  
  $this->UseClass('ListSimple');
  
class ListAdvanced extends ListSimple  
{
  
  var $template = 'list_advanced.html';
  var $template_list = 'list_advanced.html:List';
  var $template_engine = 'tree_simple.html:Tree_Engine'; //Шаблон клиентского дивжка
  var $arrows_template = 'list_advanced.html:List_Arrows';
  var $template_new = 'list_advanced.html:add_new';
  
  var $state; //персональный StateSet
  
  var $arrows; //объект постраничной рубрикации
  
  function ListAdvanced( &$config )
  {
    //упорядочиваем список
    $config->SELECT_FIELDS[] = ($config->order_field) ? $config->order_field . " as '_order'" : '_order';//'_order';
    if(!$config->order_by) $config->order_by = ( $config->order_field ? $config->order_field : "_order" ) . " ASC";//'_order ASC';
    
    //по этапу
    ListSimple::ListSimple( $config );
    
    $this->prefix = $config->module_name.'_tree_';
    //StateSet
    $this->state =& new StateSet($this->rh);
    $this->state->Set($this->rh->state);
    
    //для внутренних ссылок
    $this->url = $this->rh->url.'do/'.$config->module_name;
  }
  
  function Handle(){
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    //возможно, операции со списком
    if( $this->UpdateListStruct() )
      $rh->Redirect( $this->url.'?'.$this->state->State(0,array(),true) );
    
    //ссылка на новое
    if( !$this->config->HIDE_CONTROLS['add_new'] ){
//    $this->_add_new_href = $this->url.'?'.$this->state->State(0,array( $this->id_get_var ));
//    $tpl->Assign( '_add_new_href', $this->_add_new_href );
	
//      $tpl->Assign( '_add_new_href',  $this->rh->path_rel."do/".$this->config->module_name."?".$this->rh->state->StatePlus(0, array('_new' => 1)) );
      $tpl->Assign( '_add_new_href', $this->_href_template.$this->rh->state->StatePlus(0, array('_new' => 1)) );
      $tpl->Assign( '_add_new_title', $this->config->add_new_title ? $this->config->add_new_title : 'создать новый элемент' );
      $tpl->Parse( $this->template_new, '__add_new' );
    }
    
    //клиентский пикер
    //assign some
    $tpl->Assign('prefix',$this->prefix);
    $tpl->Assign( 'POST_STATE', $this->state->State(1) );
    $tpl->Parse( $this->template_engine, '__picker' );
    
    //постраничный рубрикатор
    $rh->UseClass('Arrows');
    $this->arrows = new Arrows( $rh );
    $this->arrows->outpice = $this->config->outpice ? $this->config->outpice : 10;
    $this->arrows->mega_outpice = $this->config->mega_outpice ? $this->config->mega_outpice : 10;

    //for custom count
    $this->arrows->count_sql = $this->count_sql;
    $this->arrows->Setup( $this->table_name, $this->where );
    $this->arrows->Set($this->state);
    $this->arrows->href_suffix = $__href_suffix;
    $this->arrows->Restore();
    if( $this->arrows->mega_sum > 1 ){
      $this->arrows->Parse('arrows.html','__links_all');
      $tpl->Parse( $this->arrows_template, '__arrows' );
    }
    $this->_href_template .= $this->arrows->State();
    
    //есть потребность прятать некоторые контроли
    $this->EVOLUTORS['controls'] = array( &$this, '_controls' );

    //по этапу
    ListSimple::Handle();
  }
  
  function UpdateListStruct(){
    $rh =& $this->rh;
    //params
    $id1 = $rh->GetVar('id1','integer');
    $id2 = $rh->GetVar('id2','integer');
    $action = $rh->GetVar('action');
    //actions

    $return = false;
    switch($action){
      case 'exchange':
        $this->result_mode = 1;
        
        DBDataView::Load("(".$this->SELECT_FIELDS[0]."='".$id1."' OR ".$this->SELECT_FIELDS[0]."='".$id2."')");
        $this->Exchange( $id1, $id2 );
        //пишем в логи
        $item1 = $this->FindById($id1);
        $item2 = $this->FindById($id2);
        $mode = $this->rh->GetVar('mode');
        $_href = $this->rh->url.'do/'.$this->config->module_name.( $mode ? '/'.$mode : '' ).'?'.$this->state->State();
        $this->rh->logs->Put( 'Список: обмен местами', $id1, $this->config->module_title, '"'.$item1[$this->SELECT_FIELDS[1]].'" - "'.$item2[$this->SELECT_FIELDS[1]].'"', $_href );
        //возвращаем
        $return = true;
      break;
    }
    return $return;
  }
  
  function _controls(&$list){ 
    $tpl =& $this->rh->tpl;
    if( !$this->config->HIDE_CONTROLS['exchange'] )
      $controls .= $tpl->parse( $list->tpl_item.':exchange' );
    return $controls;
  }
  
}
  
?>
