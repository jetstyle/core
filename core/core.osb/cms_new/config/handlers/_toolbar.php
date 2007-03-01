<?
	//рисуем тулбар
	if( $rh->render_toolbar ){
		$rh->toolbar =& $this->UseModule( $this->toolbar_module_name );
		$rh->toolbar->Handle();
	}
  
?>