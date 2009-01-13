<?
	if( $text = $rh->GetVar('text') ){
		$text = str_replace('\"','"',$text);
		$tpl->Assign('text_test',htmlspecialchars($text));
		$tpl->Assign('text',$text);
	}
	
	$tpl->Parse('test_htmlarea_2.html','html_body');
	echo $tpl->Parse('html.html');
	
?>