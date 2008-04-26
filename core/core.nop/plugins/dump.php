<?php

/**		
 *	{{!link_to class=News item=*}}	     
 *
 *	создает относительные ссылки, относительно конря сайта
 *
 *	path/to/page
 *
 */
/* если массива news нету в шаблонном дамайне, то возьмём из фикстур :P */

$item = $params['item']?$params['item']:( $params['_'] ? $params['_'] : $params[0]); // ключ
//можно {{!url_to News #NewsItem}}	     


echo '<pre>';
if (is_object($item) && get_class($item)=='ResultSet')
	echo $item;
else 
	print_r($item);
echo '</pre>';

?>
