<?
	if(!$this->rh->logs->CheckAccess()){
		echo $this->rh->tpl->Parse('access_denied.html');
		$this->rh->End();
//		$this->rh->EndError('Access denied');
	}
	
	//module config
	$this->module_title = 'Просмотр логов';
	$this->class_name = 'LogsView';
?>