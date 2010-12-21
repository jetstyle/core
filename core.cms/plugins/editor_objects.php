<?php

if ( is_array($params) && $params["_"] ) 
    $text = $params["_"];
else if ( is_array($params) && $params[0] ) 
    $text = $params[0];
else
    $text = $params;
return Locator::get('editorObjectsCorrector')->correct( $text );
?>
