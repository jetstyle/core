<?
function action_strip_tags( &$rh, &$PARAMS ){
  
  return strip_tags($PARAMS['__string']);
}

?>