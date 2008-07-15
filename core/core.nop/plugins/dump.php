<?php
$item = $params['item']?$params['item']:( $params['_'] ? $params['_'] : $params[0]); // ключ

echo '<pre>';
if (is_object($item) && get_class($item)=='ResultSet')
	echo $item;
else 
	print_r($item);
echo '</pre>';
?>