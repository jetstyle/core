<?php

$rh->useClass('Toc');
$toc =& new Toc( &$rh );		
return $toc->correct( is_array($params) ? $params['_'] : $params );

?>