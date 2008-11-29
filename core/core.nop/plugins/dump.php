<?php
$item = $params['item']?$params['item']:( $params['_'] ? $params['_'] : $params[0]); // ключ

echo '<pre>';
if (is_object($item) && get_class($item)=='ResultSet')
    echo $item;
//      print_r($item->getData());

elseif ( is_object($item) && get_class($item)=='DBModel' ) 
    print_r($item->getData());
else 
	print_r($item);
echo '</pre>';
?>