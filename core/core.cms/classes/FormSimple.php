<?php
  
  $this->useClass("DBDataEdit");
  
class FormSimple extends DBDataEdit  {
  
  var $rh; //ссылка на $rh
  var $config; //ссылка на объект класса ModuleConfig
  var $loaded = false; //грузили или нет данные?
  
  var $state; //персональный StateSet
  
  //templates
  var $template = "form_simple.html";
  var $_template_item = 'form'; //basename шаблона формы, если брать его из конфига
  var $template_loader = 'form_simple.html:head';
  var $template_item = ''; //возьмём из конфига
  var $store_to;
  var $full_path = false; //брать шаблоны не из стандартной директории?
  
  var $id_get_var = 'id';
  var $id_field = "id";
  var $id = 0; //id редактируемой записи
  
  var $supertag_limit = 20;
  var $created_field = "_created";
  
  function FormSimple( &$config )
  {
    //base modules binds
    $this->config =& $config;
    
    //DBDataEdit
//    $config->Read("form");
    $config->SELECT_FIELDS[] = '_state';
    DBDataEdit::DBDataEdit( $config->rh, $config->table_name, $config->SELECT_FIELDS, $config->where, $config->order_by, $config->limit, $config->UPDATE_FIELDS );
    $this->prefix = $config->module_name.'_form_';
    $this->new_suffix = "";
    //настройки шаблонов
    $this->store_to = "form_".$config->module_name;
    if( $config->template_item ) 
    {
    	$this->template_item = $config->template_item;
    }
    
    if( !$this->template_item )
    {
      $this->template_item = $this->rh->findScript_( $config->handlers_type, $this->config->module_name.'/'.($config->_template_item ? $config->_template_item : $this->_template_item), false, -1, 'html' );
    }
    //StateSet
    $this->state =& new StateSet($this->rh);
    $this->state->Set($this->rh->state);
    
    $this->rh->trash->table_id_field = $config->SELECT_FIELDS[0];
  }
  
  function Load(){
    if( !$this->loaded ){
      if(!$this->id)
        $this->id = $this->state->Keep( $this->id_get_var, 'integer' );
      
      DBDataEdit::Load( $this->SELECT_FIELDS[0]."='".$this->id."'" );
      if ($this->item['_state']>0)
          $this->rh->tpl->set("body_class", "class='state1'");
      $this->loaded = true;
      return true;
    }
    return false;
  }
  
  function Handle(){
    
    //load data
    if( !$this->loaded ) $this->Load();
    
    //куда делать редирект?
    $mode = $this->rh->GetVar('mode');
    $this->_redirect = $this->rh->url.'do/'.$this->config->module_name.( $mode ? '/'.$mode : '' ).'?'.$this->state->State();
    if( $this->rh->GetVar('update_2') )
      $this->_redirect .= '&update_2=1';
    
    if(!$this->rh->GLOBALS[$this->prefix.'_state'])
    {
    	$this->rh->GLOBALS[$this->prefix.'_state'] = 0 ;
    }
    
    //update data
    $redirect = $this->Delete();
    if(!$redirect) $redirect = $this->Restore();
    if(!$redirect) $redirect = $this->Update();
    
    //редирект или выставление флага, что он нужен
    if($redirect){
//      $redirect = $this->rh->url.'do/'.$this->config->module_name.'?'.$this->state->State();
      if($this->stop_redirect){
        $this->redirect = $this->_redirect;
        return;
      }else $this->rh->Redirect( $this->_redirect );
    }
    
    $tpl =& $this->rh->tpl;
    
    //подготовка нетекстовых полей
    $this->RenderFields();
    
    //назначаем движок сохранения формы без перезагрузки
    if( !$rh->__form_loader ){
      $tpl->Parse( $this->template_loader, 'html_head', true );
      $rh->__form_loader = true;
    }
    
    //обновляем родительское окно
//    if( $this->rh->GetVar('update_2') && $this->rh->GetVar('popup') )
//      $tpl->Parse( $this->template.':parent_reload', 'parent_reload' );
    
    //render form
    $item = (object)$this->item;
    $id_field = $this->SELECT_FIELDS[0];
    if( !$item->$id_field && isset($this->config->new) )
      $tpl->setRef( '*', $this->config->new );
    else
      $tpl->setRef( '*', $item );
    
    $tpl->set( 'prefix', $this->prefix );
    $tpl->set( 'POST_STATE', $this->state->State(1) );
    $tpl->set( '__form_name', $this->prefix.'_simple_form' );
    $tpl->set( '__delete_title', $item->_state!=2 ? 'удалить в корзину' : 'удалить окончательно'  );
    
    $tpl->Parse( $this->template_item, '___form');
        
    if($this->id && !$this->config->hide_delete_button )
      $tpl->Parse( $this->template.':delete_button', '_delete_button' );
    
    if(!$this->config->hide_save_button )
    {
    	if($item->_state == 2)
    	{
    		$tpl->Parse( $this->template.':restore_button', '_save_button' );
    	}
    	else 
    	{
      		$tpl->Parse( $this->template.':save_button', '_save_button' );
    	}
    }
    
    if($this->config->send_button && $item->id  && $item->_state == 0)
    {
    	if($item->sended)
    	{
    		$tpl->Parse( $this->template.':send_button_disabled', '_send_button' );
    	}
    	else 
    	{
    		$tpl->Parse( $this->template.':send_button', '_send_button' );
    	}
    }

    if(($this->config->save_button_norefresh ))
      $tpl->Parse( $this->template.':save_button_norefresh', '_save_button' );
    
    $tpl->Parse( $this->template, $this->store_to, true );
    
    //ссылка на просмотр логов
    if($this->id)
      $this->rh->logs->ParseLink( $this->config->module_title, $this->id, $this->store_to, true );
  }
  
  function RenderFields(){
    if( $this->_fields_rendered ) return;
    $this->_fields_rendered = true;
    
    $tpl =& $this->rh->tpl;
/*
  $this->config->RENDER - каждая запись в нём:
    0 - имя поля
    1 - тип поля - checkbox | select | radiobutton
    2 - хэш значений - array( id => value )
*/  
    if( is_array($this->config->RENDER) ){
      $N = count($this->config->RENDER);
      for($i=0;$i<$N;$i++){
        $row =& $this->config->RENDER[$i];
        switch( $row[1] ){
          case 'checkbox':
            $tpl->set( 'checkbox_'.$row[0], $this->item[$row[0]] ? "checked=\"checked\"" : '' );
          break;
          case 'select':
            $_str = '';
            $A =& $row[2];
            foreach($row[2] as $_id=>$_val)
            {
//            modified by geronimo
//              $_str .= "<option value='".$_id."' ".( $this->item[$row[0]]==$_id  || (!$this->item["id"] && $_id==$row[3]) ? "selected=\"selected\"" : '' ).">".$_val;
              $_str .= "<option value='".$_id."' ".(($this->item["id"] && $this->item[$row[0]]==$_id) || (!$this->item["id"] && $_id==$row[3]) ? "selected=\"selected\"" : '' ).">".$_val;
            }
            $tpl->set( 'options_'.$row[0], $_str );
          break;
          case 'radiobutton':
            //заполним по мере необходимости
          break;
        }
      }
    }
  }
  
  function Delete(){
    if( $this->rh->GetVar( $this->prefix."delete" ) )
    {
      $this->rh->logs->Put( 'Форма: '.($this->item['_state']<=1 ? 'удаление в корзину' : 'окончательное удаление'), $this->id, $this->config->module_title, $this->item[$this->SELECT_FIELDS[1]], $this->_redirect.'&_show_trash=1' );
      return $this->rh->trash->Delete( $this->config->table_name, $this->id, $this->config->module_title, $this->item[ $this->SELECT_FIELDS[1] ], $this->rh->path_rel.'?'.str_replace('&amp;','&',$this->state->StateAll()), $this->SELECT_FIELDS[0] );
    }else 
    {
        return false;
    }
  }
  
  function Restore(){
    if( $this->rh->GetVar( $this->prefix."restore" ) )
    {
      return $this->rh->trash->FromTrash( $this->config->table_name, $this->id);
    }else 
    {
        return false;
    }
  }
  
  function Update(){
    if( $this->needUpdate() )
    {
      if( $this->id )
      {
        $this->_Filters();
        DBDataEdit::Update( $this->id );
        $this->rh->logs->Put( 'Форма: модификация', $this->id, $this->config->module_title, $this->item[$this->SELECT_FIELDS[1]], $this->_redirect );
      }
      else if (!$this->config->dont_insert)
      {
        $this->_Filters( $this->new_suffix );
        $this->id = $this->new_id = $this->AddNew();
        $this->rh->logs->Put( 'Форма: добавление', $this->new_id, $this->config->module_title, $this->rh->GetVar( $this->prefix.$this->SELECT_FIELDS[1].$this->suffix.$this->new_suffix ), $this->_redirect.'&'.$this->id_get_var.'='.$this->new_id );
      }
      return true;
    }
    else 
    {
        return false;
    }
  }
  
  function needUpdate()
  {
  	return $this->rh->GetVar( $this->prefix."update" ) ? true : false;
  } 
  
  function _Filters( $suffix='' ){
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    $GLOBALS1 =& $rh->GLOBALS;
    
    if($suffix=='')
      $suffix = $this->suffix;
    
    //filter data
    if( is_array($this->config->UPDATE_FILTERS) ){
      foreach( $this->config->UPDATE_FILTERS as $field=>$filter ){
        if( is_string($field) ){
          //some field specified
          $field_name = $this->prefix . $field . $suffix;
          $GLOBALS1[ $field_name ] = $tpl->Action( $filter, $GLOBALS1[ $field_name ] );
        }else{
          //filter all fields
          $m = count($this->UPDATE_FIELDS);
          for($j=0;$j<$m;$j++){
            $field_name = $this->prefix . $this->UPDATE_FIELDS[$j] . $suffix;
            $GLOBALS1[ $field_name ] = $tpl->Action( $filter, $GLOBALS1[ $field_name ] );
          }
        }
      }
    }
    
    //pre-rendering
    if( is_array($this->config->PRE_FILTERS) ){
      foreach( $this->config->PRE_FILTERS as $filter=>$FIELDS ){
        foreach($FIELDS as $field){
          $field_pre = $field.'_pre';
          //пререндеринг содержимого поля
          $GLOBALS1[ $this->prefix.$field_pre.$suffix ] = $tpl->Action( $filter, isset($GLOBALS1[ $this->prefix.$field_pre.$suffix ]) ? $GLOBALS1[ $this->prefix.$field_pre.$suffix ] : $GLOBALS1[ $this->prefix.$field.$suffix ] );
          $this->item[$field_pre] = $GLOBALS1[ $this->prefix.$field_pre.$suffix ];
          //добавляем поле в список для сохранения
          $this->UPDATE_FIELDS[] = $field_pre;
        }
      }
    }
    
    //supertag
    if( $this->config->supertag ){
      if( is_array($this->config->supertag) ){
        $field = $this->config->supertag[0];
        $limit = $this->config->supertag[1];
      }else{
        $field = $this->config->supertag;
        $limit = $this->supertag_limit;
      }
      $rh->UseClass('Translit');
      $translit =& new Translit();
      $rh->GLOBALS[ $this->prefix.'_supertag'.$suffix ] = $translit->TranslateLink( $rh->GLOBALS[ $this->prefix.$field.$suffix ], $limit );
      if ($this->config->supertag_check)
      {
         $sql = "SELECT id, _supertag FROM ".$this->config->table_name." WHERE _supertag=".$rh->db->quote($rh->GLOBALS[ $this->prefix.'_supertag'.$suffix ]);
         $rs = $rh->db->execute($sql);
         //$all = $db->getArray();
         if ($rs)
         {
            $rh->GLOBALS[ $this->prefix.'_supertag'.$suffix ] .= "_".$this->id;
         }
      }
      $this->UPDATE_FIELDS[] = '_supertag';
    }
  }
  
  function AddNew(){
    //add new
    $id = DBDataEdit::AddNew( $this->config->INSERT_FIELDS );
    //set _created,_order
    $this->rh->db->Execute("UPDATE ".$this->table_name." SET ".$this->created_field."=NULL,_order=".$this->SELECT_FIELDS[0]." WHERE ".$this->SELECT_FIELDS[0]."='$id'");
    //return $id
    return $id;
  }
}
  
?>