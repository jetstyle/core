<?
/*
	Оборачивает содержимое в плоскую обёртку. 
*/
function action_wrap( &$rh, &$PARAMS ){
	
	$rh->tpl->assign("html_body",$PARAMS['__string']);

	$template = $PARAMS[0] ? $PARAMS[0] : $PARAMS["template"];
	if($template=='')
		$rh->debug->Error("Wrapp: \$template is empty.");
  
	return $rh->tpl->parse( $template );
}

?>