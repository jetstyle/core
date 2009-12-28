<?php
if (!is_array($params)) $params = array("_"=>$params);
$text = $params["_"]?$params["_"]:$params[0];

if ($text == "") return;

$typo = &Locator::get('typografica');
echo $typo->correct($text, false);
?>