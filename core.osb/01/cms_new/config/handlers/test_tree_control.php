<?
	
	//первый конфиг
	$rh->UseClass("ModuleConfig");
	$config =& new ModuleConfig( $rh, 'test' );
	
	//Ёмулируем вчтение конфига из модул€
	$config->class_name = 'TreeControl';
	$config->table_name = 'esk_faq_comments';
	$config->SELECT_FIELDS = array('id','fio');
	
	//основной модуль
	$module =& $config->InitModule();
	$module->store_to = 'html_body';
	$module->_href_template = $rh->path_rel.'test_tree_control?';
	$module->Handle();
	
	echo $tpl->Parse( "html.html" );
	
?>