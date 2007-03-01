<?
/*
	$WRAPPED = array( "tree", "form" );
*/
	
	$this->UseClass('Wrapper',1);
	
class TreeForm extends Wrapper {
	
	function Handle(){
		
		//ссылка с рубрикатора
		$_href = $this->config->_href_template ? $this->config->_href_template : $this->rh->path_rel."do/".$this->config->module_name.'/?'.$this->rh->state->State();
		$this->MODULES[0]->_href_template = $_href;
		
		$tree =& $this->MODULES[0];
		$form =& $this->MODULES[1];
		
		//иногда нужно пост-обработка
		$form->stop_redirect = true;
		
		//вся машинерия агрегированных классов
		Wrapper::Handle();
		
		//возможно, добавили новый элемент
		/* было нужно в дервере, но с применением умолчаний и переходом на новый контрол видимо стало не нужно */
//		if( $form->new_id ) $this->MODULES[0]->Restore(); 
/*		if( $form->deleted && method_exists($tree,'Delete') ){
//			$tree->Delete($form->deleted);
			$this->rh->EndError('Здесь было $form->deleted && method_exists($tree...');
		}*/
		if( $form->redirect ) $this->rh->Redirect( $form->redirect );
	}
	
}	
?>