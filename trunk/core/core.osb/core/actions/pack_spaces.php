<?
function action_pack_spaces( &$rh, &$PARAMS ){
	
	return preg_replace( "/[\n\r]+/", " ", $PARAMS['__string'] );
}

?>