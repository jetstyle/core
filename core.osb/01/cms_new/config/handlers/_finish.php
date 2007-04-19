<?
	include( $rh->FindScript('handlers','_page_attrs') );

	if( $rh->GetVar("popup") )
		$tpl->parse("popup.html","html_body");
	
	echo $tpl->Parse( "html.html" );

?>