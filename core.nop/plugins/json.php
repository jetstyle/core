<?php
$data = $params["_"]?$params["_"]:$params[0];
if (!is_array($data))
{
	$data = array();
}
Finder::useClass('Json');
return Json::encode($data);
?>