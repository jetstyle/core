<?php
	//if (!isset($params['_']))
		$params['_'] = $tpl->parse($params[0]);

//	var_dump($params);
	$tpl->setRef("*", $params);
	//$tpl->set("", $params["_"]);
	
	$ret = $tpl->parse("_/widget.html");
	echo $ret;
?>