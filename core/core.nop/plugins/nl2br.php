<?php
	if (!is_array($params)) $params = array("_"=>$params);
	$text = $params["_"]?$params["_"]:$params[0];	
	return nl2br($text);
?>