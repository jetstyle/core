<?
	
function action_editor( &$rh, $PARAMS ){
	
	$tpl =& $rh->tpl;
	
	extract($PARAMS);
	
	$tpl->Assign(array(
		"__input_name"=> $tpl->GetAssigned( $tpl_prefix ) . $input_name,
		"__form_name"=> $form_name ? $form_name : $tpl->GetAssigned('__form_name'),
		"__cols"=> $cols ? $cols : 70,
		"__rows"=> $rows ? $rows : 20,
		"__width"=> $width ? $width : 580,
		"__height"=> $height ? $height : 500,
		"__text"=> $__string,
		"__style"=> "", //потом прикрутим
	));
	
	return $tpl->Parse( $template ? $template : "forms/editor.html" );
}
	
?>