<?php
	if (is_array($params))
		$params = $params['_'];
	
  $ret = strip_tags($params);
  return $ret;
?>