<?
  
  $this->UseClass('ListSimple');
  
/**
 * ListPlain для неупорядоченных списков
 */
class ListPlain extends ListSimple  
{
  
  var $template = 'list_plain.html';
  var $template_list = 'list_plain.html:List';
  var $arrows_template = 'list_plain.html:List_Arrows';
  var $template_new = 'list_plain.html:add_new';
	
  
  var $state; //персональный StateSet
  
  var $arrows; //объект постраничной рубрикации
  
  function ListPlain( &$config )
  {
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
    
    //ссылка на новое
    if( !$this->config->HIDE_CONTROLS['add_new'] ){
//    $this->_add_new_href = $this->url.'?'.$this->state->State(0,array( $this->id_get_var ));
//    $tpl->Assign( '_add_new_href', $this->_add_new_href );
      $tpl->Assign( '_add_new_href', $this->_href_template );
      $tpl->Assign( '_add_new_title', $this->config->add_new_title ? $this->config->add_new_title : 'создать новый элемент' );
      $tpl->Parse( $this->template_new, '__add_new' );
    }
    
    //клиентский пикер
    //assign some
    $tpl->Assign('prefix',$this->prefix);
    $tpl->Assign( 'POST_STATE', $this->state->State(1) );
    
    //постраничный рубрикатор
    $rh->UseClass('Arrows');
    $this->arrows = new Arrows( $rh );
    $this->arrows->outpice = $this->config->outpice ? $this->config->outpice : 10;
    $this->arrows->mega_outpice = $this->config->mega_outpice ? $this->config->mega_outpice : 10;
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
  
  function _controls(&$list){ 
    $tpl =& $this->rh->tpl;
	 /*
    if( !$this->config->HIDE_CONTROLS['exchange'] )
      $controls .= $tpl->parse( $list->tpl_item.':exchange' );
	  */
    return $controls;
  }
  
}
  
?>
