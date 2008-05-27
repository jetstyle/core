<?php
if (is_array($params))
	$params = $params['_'];	
	//$params['_']

$rh->useClass('Toc');
$toc =& new Toc( &$rh );		

$ret = $toc->correct( $params );
return $ret;

?>