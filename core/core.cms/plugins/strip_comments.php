<?php
	if (is_array($params))
		$params = $params['_'];



	$ret = preg_replace( "/\s*<\!--(keep|#)/s", "<!--", 
	    	   	preg_replace( "/\s*<\!--(?!keep|#).*?-->\s*/s", "", 
	    	   	$params
	    		)
    		);
   	
  return $ret;
?>