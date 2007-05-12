<?php

/**		
 * # ������ �� item �� �������� 'News'
 *	{{!link_to class='News' item=*}}	     
 *
 * # ������ �� �������� 'News'
 *	{{!link_to class='News'}}
 *	{{!link_to 'News'}}
 *
 * # ������ �� item
 * # � ���� ������, item ������ ��������� 
 * # ���� 'ContentType' -- ��� �������
 * # ��� ���� 'link' -- link �� �������� FIXME: ���� ��� ������������� -- 
 * #	  ����� ������� �����
 *	{{!link_to item=*}}	     
 *	{{!link_to *}}	     
 *
 *	������� ������������� ������, ������������ ����� �����
 *
 *	path/to/page
 *
 */

$url = NULL;

$class = $params['class']?$params['class']:$params[0]; // ���
$item =  $params['item']?$params['item']:$params[1]; // ��������


if (isset($class) && !is_scalar($class))
	// ������ ���������� �������� item
{
	$item = $class;
	unset($class);
}

if (!isset($class))
{
	// lucky: FIXME -- $item �� ���� ��� ������
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
