<?php
if (!is_array($params)) $params = array("_"=>$params);
$text = $params["_"]?$params["_"]:$params[0];

if ($text == "") return;

$typo = &Locator::get('typografica');

$typo->settings["dashglue"] = false;
$typo->settings["dashwbr"] = true;

return $typo->correct( $text, false );
?>