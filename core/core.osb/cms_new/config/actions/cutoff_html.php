<?
function action_cutoff_html( &$rh, &$PARAMS ){
	return preg_replace( "/\<\/body.*?\>.*/is", "", preg_replace( "/.*\<body.*?\>/is", "", $PARAMS['__string'] ) );
}

?>