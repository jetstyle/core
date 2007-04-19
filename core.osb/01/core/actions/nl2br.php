<?php

/*
	nl2br() wrapper
*/

function action_nl2br( &$rh, $PARAMS ){
	
	return nl2br($PARAMS['__string']);

}

?>
