<?php

if ( is_array($params) && isset($params["_"]) ) 
    $text = $params["_"];
else if ( is_array($params) && isset($params[0]) ) 
    $text = $params[0];
else
    $text = $params;

if ($text == "") return;

return Locator::get('editorObjectsCorrector')->correct( $text );
?>
