<?php

$rh->useClass('Toc');
$toc =& new Toc( &$rh );		
return $toc->correct( $params['_'] );

?>