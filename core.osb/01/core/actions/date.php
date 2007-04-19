<?php

/*
	Wrapper for date()
*/

function action_date( &$rh, $PARAMS ){

	return date( $PARAMS[0], strtotime($PARAMS['__string']) );

}

?>
