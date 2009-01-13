<?php
	if (is_array($params))
		$params = $params['_'];	
	
	return nl2br($params);
?>