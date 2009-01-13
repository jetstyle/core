<?php

/*
	Засовывает скрипт в onload
*/

function action_onload( &$rh, $PARAMS ){
	
	//$rh->tpl->Assign("_",$PARAMS['__string']);
	//return $rh->tpl->parse("script.html");
    $rh->tpl->Assign("html_onload",$PARAMS['__string'],1);
}

?>
