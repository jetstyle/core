<?php

/**		
 *	{{!link_to class=News item=*}}	     
 *
 *	������� ������������� ������, ������������ ����� �����
 *
 *	path/to/page
 *
 */
/* ���� ������� news ���� � ��������� �������, �� ������ �� ������� :P */

$item = $params['item']?$params['item']:( $params['_'] ? $params['_'] : $params[0]); // ����
//����� {{!url_to News #NewsItem}}	     


echo '<pre>';
if (is_object($item) && get_class($item)=='ResultSet')
	echo $item;
else 
	print_r($item);
echo '</pre>';

?>
