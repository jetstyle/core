<?php
/*
	$WRAPPED = array( "tree", "form" );
*/
	
$this->useClass('Wrapper');
	
class TreeForm extends Wrapper {
	
	function Handle()
	{	
		//ссылка с рубрикатора
		$_href = $this->config->_href_template ? $this->config->_href_template : $this->rh->path_rel."do/".$this->config->module_name.'/?'.$this->rh->state->State();
		$this->MODULES[0]->_href_template = $_href;
		
		$tree =& $this->MODULES[0];
		$form =& $this->MODULES[1];
		
		//иногда нужно пост-обработка
		$form->stop_redirect = true;
		
		//вся машинерия агрегированных классов
		Wrapper::Handle();
		
		if( $form->redirect ) $this->rh->Redirect( $form->redirect );
	}
	
}	
?>