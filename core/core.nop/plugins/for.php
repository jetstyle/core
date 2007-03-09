	<?php

	/*		{{!for each=news do=test.html:news}}	     */
	/* ���� ������� news ���� � ��������� �������, �� ������ �� ������� :P */

	$key = $params['each']?$params['each']:$params[0]; // ����
	//����� ��� each= � �����, {{!for news do=test.html:news}}	     

	$alias = $params['as']?$params['as']:NULL; // �����
	//����� {{!for news as news_item do=test.html:news}}	     

	$template_name = $params['do']?$params['do']:$params['use']; // ����
	//����� {{!for news use=test.html:news}}	     

	$caller = $params['_caller'];
	if ($template_name[0]==':')
		$template_name = $caller.'.html'.$template_name;   

	if (isset($alias)) $item_store_to = $alias;
	else $item_store_to = '*';

	$data_sources = array(); // ��� ����� ������ ������ ��� each

	if ($key{0} == '*')
	{ // ��������� ���������� *var
		$data_sources[] = '$tpl->Get("*")';
		$key = substr($key, 1);
	}
	elseif ($key{0} == '#')
	{  // ��������� ���������� #obj
		$data_sources[] = '$tpl->domain';
		$key = substr($key, 1);
	}
	else
	{ // ������ �� ����� ����������
		// lucky: ���� �� �������: 
		//		��� ��� ���� �������? 
		//		� ����� ������ �����?
		$data_sources[] = '$rh->tpl_data';
	}
	// ����� �� ����������
	// lucky: ��� $key ��� ��� ���������
	$data_sources[] = '((($_t=array_shift(explode(".","'.$key.'"))) && ($s = $rh->FindScript( "fixtures", $_t))) ? array($_t=>include $s):NULL)';

	$tpl->_SpawnCompiler(); // �������� ��� ���������� ���������������
	$value = $tpl->compiler->_ConstructGetValue($key);
	foreach ($data_sources as $source)
{
	$expr = '$_ = '.$source.'; return '.$value.';';
	if (($val_arr = eval($expr)))
		break;
}

/*
 * lucky:
 * ���� ��������� �������� ��������� each
 *
 * ����� ���, ��� ������ ���������� ���, ��� ������
 * ������ ��������� ������.. � ����?
 *
 * �� �.�., ���� ��� �������� �����, � ���� ����� ��������� ����������,
 * �� ��������� �� ���� ������ ������ ��� ��������� ������.
 *
 * � ������, �������� ���� ����������� �������������,
 * ������ ��� � ����������� �� ������������� ����������� ��������.
 *
 * ��� ��� ����� � ���� ������.
 *
 * ���� ��� ����:
 *		1. ���������� �� ������������� ����������� � ��������.
 *			� ��������� ��������� ������� ��������� ����������, ��������� ��� ��������.
 *			+ ��������
 *			- �������������
 *		2. ������� ����� � ��������, ������� ����� ����������, ����� ��������� ������ ��� ��������
 *				��������, ��� ����� ��������� ������ ���������� � �������� �������� -- ��� ����� ���, 
 *				� �������� �������, �������� �����������.
 *		3. ������� ��������� ������ ������ ������� ��������� � ���������������� ������� �������.
 *			����� ������� -- inline'���.
 * 
 * ������, �������, �� ����������� ����� �� ����������, ������ ��� ����������� ������ �� ��������
 * 
 * /
 
if ( $key[0]=='*' ) // *attr.attr
{
	$key = substr($key, 1);
	$val_arr =& $rh->tpl->Get("*");
}
elseif ($key[0] == '#') // lucky@npj #object.attr
{
	$p = strpos($key, '.');
	$o = substr($key, 1,$p-1);
	$key = substr($key, $p+1);
	$val_arr =& $rh->tpl->Get($o);
}
else
{
	$val_arr =& $rh->tpl_data;
}

// lucky@npj: �������������� object.attr.attr
if (!empty($key))
{
	$keys = explode('.', $key);
	foreach ($keys as $k)
	{
		if (array_key_exists($k, $val_arr)) $val_arr =& $val_arr[$k];
		else { unset ($val_arr); break; }
	}
}

if (!isset($val_arr))
{
	// ����� �� ����������
	$v = include $rh->FindScript( 'fixtures', $key );
	// lucky@npj: �������������� object.attr.attr
	$keys = explode('.', $key);
	while ($k = array_shift($keys))
	{
		if (array_key_exists($k, $v)) $v =& $v[$k];
		else { unset ($v); break; }
	}
	$val_arr =& $v;
	unset($v);
}

 */


if(is_array($val_arr) && !empty($val_arr))
{
	if(!(strpos($template_name, ':') === false))
	{
		$sep_tpl = $template_name.'_sep';
		$item_tpl = $template_name.'_item';
	}
	else 
	{
		$sep_tpl = $template_name.':sep';
		$item_tpl = $template_name.':item';
	}

	$sep = $rh->tpl->parse($sep_tpl);
	// ���� ����� ��� ����� � �� ����

	$content = '';

	$old_ref =& $rh->tpl->Get('*');

	foreach($val_arr AS $r)
	{
		if (is_array($r))
		{
			$rh->tpl->SetRef($item_store_to, $r);
		}
		else
			$rh->tpl->Set('_', $r);
		$content .= ($content ? $sep : '').$rh->tpl->parse($item_tpl);
	}

	$rh->tpl->SetRef('*', $old_ref );
}
else
{
	if(!(strpos($template_name, ':') === false))
	{
		$empty_tpl = $template_name.'_empty';
	}
	else 
	{
		$empty_tpl = $template_name.':empty';
	}
	$content = $rh->tpl->parse($empty_tpl);
}

echo $content;
$content = '';

?>
