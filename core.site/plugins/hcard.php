<?php
//@microformats/hcard.html

/**
 * For more details see class Hcard 
 */

Finder::useClass('Hcard');

$template = 'microformats/hcard.html'; 

$tpl->pushContext();
$tpl->set('*', Hcard::format($params['_']));
echo $tpl->parse($template);
$tpl->popContext();
?>