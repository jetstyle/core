<?php
//@_/swfobject.html
/*
SWF magic     {{!flash someflash.sfw}}
{{!flash shapka1.swf transparent=1 height="670" version=8 instant=1}}
-----------

��������� ���� � ������� swfobject.js

���������:
  [0]         -- path/filename.swf ������������ ������������ ��������
  transparent -- �� ��������� "false"
  width       -- �� ��������� 100%
  height      -- 
  scale       -- �� ��������� "noscale"
  id          -- �� ��������� ������ ��� ����� ��� ����������
  container   -- ���� ������ ����. �� ��������� �� ���������
                 ��������� ��� <div id="{{id}}_container">,
                 � ���� ���� � �������.

*/

// ��������� ������ ��������� ������� �����,
// ��� ��� ����� ������������ swfobject.js

// ��������� �� ������� ������ ������� �����

$src = $params['src'] = $params['src'] ? $params['src'] : $params[0];
unset ($params[0]);

if (!$src)
	return;

if (!$params['fullpath'])
{
	Locator::get( 'tpl' )->set("prepath", Locator::get('tpl')->get('images')."flash/" );
}

//�������� ��������� �� �����
list ($filename, $query) = explode("?", $src);

$id = basename($filename, ".swf");
if (!$container = $params['container'])
{
	$container = $id . '_container';
	$generate_container = true;
}

$defaults = array (
	'src' => '',
	'width' => "100%",
	'height' => "100%",
	'id' => $id,
	'container' => $id . '_container',
	'generate_container' => $generate_container,
	'version' => "7",
	'scale' => "noscale",
	'salign' => "l",
	'bgcolor' => "#ffffff",
	'quality' => "high",
	'menu' => "false",
	'xiRedirectUrl' => "",
	'redirectUrl' => "",
	'detectKey' => "",
	'transparent' => "",
);

$ignores = array (
	'instant'
);
foreach ($ignores as $i)
{
	unset ($params[$i]);
}
foreach ($params as $k => $v)
{
	if ($k[0] == "_")
		unset ($params[$k]);
}

foreach ($defaults as $k => $v)
{
	if ($params[$k])
	{
		$defaults[$k] = $params[$k];
	}
	//$tpl->set( $k, $params[$k]?$params[$k]:$v );
	//echo ($k.'='.($params[$k]?$params[$k]:$v).'<br/ >');
}

// ��������� -- flashvars
$flashvar_keys = array_diff(array_keys($params), array_keys($defaults));
foreach ($flashvar_keys as $k)
{
	$tpl->set('var_key', $k);
	$tpl->set('var_value', $params[$k]);
	$defaults['flashvars'] .= $tpl->parse("_/swfobject.html:Variable_Item");
}

$tpl->set('f', $defaults);
echo $tpl->Parse("_/swfobject.html:body");
?>
