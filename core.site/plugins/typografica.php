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

$typo = &Locator::get('typografica');

$typo->settings["dashglue"] = false;
$typo->settings["dashwbr"] = true;

return $typo->correct( $text, false );
?>