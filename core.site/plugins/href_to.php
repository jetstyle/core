<?php
//!link_to

/**		
 * {{!href_to class=News item=*}}	     
 *
 * Создает ссылки относительно корня сервера
 * /path/to/site/path/to/page
 *
 */

echo RequestInfo::$baseUrl.$tpl->Action('link_to', $params);

?>
