<?
function action_strip_comments( &$rh, &$PARAMS ){
  
  return 
    preg_replace( "/\s*<\!--(keep|#)/s", "<!--", 
    preg_replace( "/\s*<\!--(?!keep|#).*?-->\s*/s", "", 
    $PARAMS['__string'] 
    ));
}

?>