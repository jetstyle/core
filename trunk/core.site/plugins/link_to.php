<?php

/**		
 * # ������ �� item �� �������� 'News'
 *	{{!link_to class='News' newsItem}}
 *
 * # ������ �� �������� 'News'
 *	{{!link_to 'News'}}
 */

$url = NULL;

$class = $params[0]; 
$item =  $params[1];

if (isset($class))
{
	$url = Router::linkTo($class, $item);
}

if (isset($url)) echo $url;
else echo '__page_not_found';
?>