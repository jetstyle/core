<?php

$string = isset($params[0]) ? $params[0] : $params['_'];
	
$res = iconv($params['in'], $params['out'], $string);

echo $res;

?>