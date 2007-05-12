<?php

/**		
 * # ссылка на item на странице 'News'
 *	{{!link_to class='News' item=*}}	     
 *
 * # ссылка на страницу 'News'
 *	{{!link_to class='News'}}
 *	{{!link_to 'News'}}
 *
 * # ссылка на item
 * # в этом случае, item должен содержать 
 * # поле 'ContentType' -- тип объекта
 * # или поле 'link' -- link на страницу FIXME: пока как совместимость -- 
 * #	  потом удалить нафик
 *	{{!link_to item=*}}	     
 *	{{!link_to *}}	     
 *
 *	создает относительные ссылки, относительно конря сайта
 *
 *	path/to/page
 *
 */

$url = NULL;

$class = $params['class']?$params['class']:$params[0]; // тип
$item =  $params['item']?$params['item']:$params[1]; // значение


if (isset($class) && !is_scalar($class))
	// первым аргументом передали item
{
	$item = $class;
	unset($class);
}

if (!isset($class))
{
	// lucky: FIXME -- $item не факт что массив
	if (isset($item['href'])) 
	{
		$url = $item['href'];
	} else 
	if (isset($item['ContentType']))
	{
		$class = $item['ContentType'];
	}
}


if (isset($class))
{
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

	$p =& $rh->getPageByContentType($page_cls);
	if ($p)
	{
		$url = $p->url_to($item_cls, $item);
	}
}

if (isset($url)) echo $url;
else echo '__page_not_found';

?>
