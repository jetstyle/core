<?php
//!link_to

/**		
 * {{!href_to class=News item=*}}	     
 *
 * ������� ������ ������������ ����� �������
 * /path/to/site/path/to/page
 *
 */

echo Config::get('front_end_path').$tpl->Action('link_to_fe', $params);

?>
