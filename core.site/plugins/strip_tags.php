<?php
	if (is_array($params))
		$input = $params['_'];
	else
	    $input = $params;
	
  $ret = strip_tags($input);
  return $ret;
?>
