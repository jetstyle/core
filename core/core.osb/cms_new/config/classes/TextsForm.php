<?
	
	$this->UseClass('FormSimple');
	
class TextsForm extends FormSimple {
  
  var $template_item = 'texts_form.html';
  
  function Handle(){
    $tpl =& $this->rh->tpl;
    
    $this->Load();
    
    $tpl->assign('prefix',$this->prefix);
    $tpl->AssignRef('*',$this->item);
    
    if( $this->item['type']==1 )
      $tpl->parse( $this->template_item.':text_plain', 'text' );
    else
      $tpl->parse( $this->template_item.':text_rich', 'text' );
    
    FormSimple::Handle();
  }
	
	function Update(){
		$rh =& $this->rh;
    
		if( $rh->GLOBALS[ $this->prefix.'_supertag'.$this->suffix ]=='' )
			$this->config->supertag = 'title';
    
		return FormSimple::Update();
	}
	
}
	
?>