<?php
  return 
    preg_replace( "/\s*<\!--(keep|#)/s", "<!--", 
    preg_replace( "/\s*<\!--(?!keep|#).*?-->\s*/s", "", 
    $params['_']
    ));
?>