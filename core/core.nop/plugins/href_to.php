<?php

/**		
 * {{!href_to class=News item=*}}	     
 *
 * Создает ссылки относительно корня сервера
 * /path/to/site/path/to/page
 *
 */

echo $rh->base_url.$tpl->Action('link_to', $params);

?>
