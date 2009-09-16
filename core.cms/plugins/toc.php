<?php
Finder::useClass('Toc');
$toc = new Toc();
return $toc->correct( is_array($params) ? $params['_'] : $params );
?>