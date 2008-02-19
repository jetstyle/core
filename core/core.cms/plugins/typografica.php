<?php
if(!isset($rh->typo))
{
	$rh->UseClass('Typografica');
	$rh->typo =& new Typografica( &$rh );
	$rh->typo->settings["dashglue"] = false;
	$rh->typo->settings["dashwbr"] = true;
}	

return $rh->typo->correct( $params['_'], false );
?>