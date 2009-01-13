<?php
//@microformats/hcard.html

/**
 * For more details see class Hcard 
 */

Finder::useClass('Hcard');

$template = 'microformats/hcard.html'; 

$data = array('*' => Hcard::format($params['_']));
$stackId = $tpl->addToStack($data);
echo $tpl->parse($template);
$tpl->freeStack($stackId);
?>