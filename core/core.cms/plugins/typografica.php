<?php
if(!isset($rh->typo))
{
	Finder::useClass('Typografica');
	$rh->typo =& new Typografica( &$rh );
	$rh->typo->settings["dashglue"] = false;
	$rh->typo->settings["dashwbr"] = true;
}

return $rh->typo->correct( $params, false );
?>