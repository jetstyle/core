<?php

$string = $params['_'];
$limit = $params[0];

if(strlen($string) < $limit)
	return $string;
else {
	$string = html_entity_decode($string);
	if(preg_match('/[^\w]/', $string, $ms, PREG_OFFSET_CAPTURE, $limit-6)) {
		return substr($string, 0, $ms[0][1]).'...';		
	}else
		return substr($string, 0, $limit-3).'...';
} 


?>