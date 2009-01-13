<?
	//первый конфиг
	$rh->UseClass("ModuleConfig");
	$config =& new ModuleConfig( $rh, 'test' );
	
	//Ёмулируем вчтение конфига из модул€
	$config->class_name = 'TreeControl';
	$config->table_name = 'esk_content';
	$config->SELECT_FIELDS = array('id','title','mode');
	
	//нам нужен только XML
	$rh->GLOBALS['action'] = 'xml';
	
	//основной модуль
	$module =& $config->InitModule();
	$module->store_to = "html_body";
	$module->Handle();
	
?>