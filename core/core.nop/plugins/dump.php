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

$item = $params['item']?$params['item']:$params[0]; // ����
//����� {{!url_to News #NewsItem}}	     


echo '<pre>';
print_r($item);
echo '</pre>';

?>
