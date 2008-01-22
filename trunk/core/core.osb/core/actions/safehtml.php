<?php
function action_safehtml( &$rh, &$PARAMS ){
	
	if( !isset($rh->safehtml) ){
		
		$rh->UseLib('safehtml/safehtml');
    	$rh->safehtml = new safehtml();
    	
    	
	}	
	
	return $rh->safehtml->parse( $PARAMS['__string'] );
}
?>