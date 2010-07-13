<?php
/*
if (!is_array($params)) $params = array("_"=>$params);
$text = $params["_"]?$params["_"]:$params[0];


*/

if ( is_array($params) && $params["_"] ) 
    $text = $params["_"];
else if ( is_array($params) && $params[0] ) 
    $text = $params[0];
else
    $text = $params;

if ($text == "") return;


Finder::useClass('Translit');
$translit = new Translit();

$limit = $params["limit"] ? $params["limit"] : 64;

$res = $translit->supertag( $text, TR_NO_SLASHES, $limit );

return $res;
?>
