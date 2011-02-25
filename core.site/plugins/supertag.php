<?php
	$text = $params["_"]?$params["_"]:$params[0];
	
	Finder::useClass('Translit');
	$translit = new Translit();
	return $translit->supertag( $text, TR_NO_SLASHES, 50 );
?>