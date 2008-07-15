<?php
//!href_to
/**
 * {{!url_to class=News item=*}}	     
 *
 * создает полные ссылки на страницу
 *
 * http://server/path/to/site/path/to/page
 */

echo $rh->host_url.$tpl->action('href_to', $params);

?>
