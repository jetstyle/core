<?php
$str = $params['_'];
$name = $params['name'];

$pos = stripos($str, $name, strlen($str)-strlen($name));

return trim(substr($str, 0, $pos));
?>