<?
	//������ ������
	$rh->UseClass("ModuleConfig");
	$config =& new ModuleConfig( $rh, 'test' );
	
	//��������� ������� ������� �� ������
	$config->class_name = 'TreeControl';
	$config->table_name = 'esk_content';
	$config->SELECT_FIELDS = array('id','title','mode');
	
	//��� ����� ������ XML
	$rh->GLOBALS['action'] = 'xml';
	
	//�������� ������
	$module =& $config->InitModule();
	$module->store_to = "html_body";
	$module->Handle();
	
?>