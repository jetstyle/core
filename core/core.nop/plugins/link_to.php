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

$class = $params['class']?$params['class']:$params[0]; // ключ
//можно без each= а сразу, {{!url_to News/NewsItem}}	     

$item = $params['item']?$params['item']:$params[1]; // ключ
//можно {{!url_to News #NewsItem}}	     

$clss = explode('/', $class, 2);
if (count($clss) == 2)
{
	list($page_cls, $item_cls) = $clss;
}
else
{
	$page_cls = $class;
	$item_cls = NULL;
}

$url = NULL;
$p =& $rh->getPageByContentType($page_cls);
if ($p)
{
	$url = $p->url_to($item_cls, $item);
}

if (isset($url)) echo $url;
else echo '__page_not_found';

?>
