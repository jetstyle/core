<?
  
  $this->UseClass('FormFiles',1);
  
class FormIframe extends FormFiles {
  
//  var $template_item = 'faq_form.html';
  
  function Handle(){
    $tpl =& $this->rh->tpl;
    
    //load item
    $this->Load();
    
    //добавляем iframe с редактированием вопросов
    if( $this->item['id'] )
    {
      $tpl->Assign('prefix',$this->prefix);
      $tpl->Assign( '__url', $this->rh->path_rel.$this->config->href_for_iframe.$this->id.'&hide_toolbar=1' );
      //die($this->rh->path_rel.$this->config->href_for_iframe.$this->id.'&hide_toolbar=1' );
      $tpl->Parse( 'iframe.html', '_iframe' );

    }else
      $tpl->Assign('_iframe','<br />');
    
    //по этапу
    FormFiles::Handle();
  }
  
  function Update(){
    $rh =& $this->rh;
    $db =& $rh->db;
    
    if( $rh->GLOBALS[ $this->prefix.'_supertag'.$this->suffix ]=='' )
      $this->config->supertag = 'title';
    
    if( $this->config->update_tree ){
      if( !FormSimple::Update() ) return false;
      include( $rh->FindScript('handlers','_update_tree_pathes') );
      return true;
    }
    
    return FormFiles::Update();
  }
  
}
  
?>