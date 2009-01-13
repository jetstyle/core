<?php 
$txt = $params["_"]?$params["_"]:$params[0];
$length = $params['length'] ? $params['length'] : 200;
$addDots = $params['add_dots'];

$txt = trim($txt);
if(strlen($txt) <= $length)
{
	return $txt;
}
$_txt = substr($txt, 0, $length);
$_txt = trim(substr($_txt, 0, strrpos($_txt, ' '))).($params['add_dots'] ? '...' : '');
return $_txt;
?>