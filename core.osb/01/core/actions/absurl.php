<?php

/*
	Работает неверно!! Использовать не рекомендуется.
*/

function action_absurl( &$rh, $PARAMS ){

	return preg_replace(
		"<a([^>]*)href=([\"']*)(?!http\:)(?!mailto)",
		"<a\\1 href=\\2".$rh->url,
		$PARAMS['__string']
	);
}

?>
