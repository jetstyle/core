<?php

/*
	����������� ����� ���-������� � ���������� ������.
*/

function action_script( &$rh, $PARAMS ){
	
	$rh->tpl->Assign("_",$PARAMS['__string']);
	return $rh->tpl->parse("script.html");

}

?>
