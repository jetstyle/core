	<?php

	/*		{{!for each=news do=test.html:news}}	     */
	/* если массива news нету в шаблонном дамайне, то возьмём из фикстур :P */

	$key = $params['each']?$params['each']:$params[0]; // ключ
	//можно без each= а сразу, {{!for news do=test.html:news}}	     

	$alias = $params['as']?$params['as']:NULL; // алиас
	//можно {{!for news as news_item do=test.html:news}}	     

	$template_name = $params['do']?$params['do']:$params['use']; // ключ
	//можно {{!for news use=test.html:news}}	     

	$caller = $params['_caller'];
	if ($template_name[0]==':')
		$template_name = $caller.'.html'.$template_name;   

	if (isset($alias)) $item_store_to = $alias;
	else $item_store_to = '*';

	$data_sources = array(); // тут будем искать данные для each

	if ($key{0} == '*')
	{ // шаблонная переменная *var
		$data_sources[] = '$tpl->Get("*")';
		$key = substr($key, 1);
	}
	elseif ($key{0} == '#')
	{  // шаблонная переменная #obj
		$data_sources[] = '$tpl->domain';
		$key = substr($key, 1);
	}
	else
	{ // данные из среды выполнения
		// lucky: пока не понятно: 
		//		как они туда попадут? 
		//		и нафиг вообще нужны?
		$data_sources[] = '$rh->tpl_data';
	}
	// пошли за фикстурами
	// lucky: тут $key уже без префиксов
	$data_sources[] = '((($_t=array_shift(explode(".","'.$key.'"))) && ($s = $rh->FindScript( "fixtures", $_t))) ? array($_t=>include $s):NULL)';

	$tpl->_SpawnCompiler(); // убедимся что компилятор инициализирован
	$value = $tpl->compiler->_ConstructGetValue($key);
	foreach ($data_sources as $source)
{
	$expr = '$_ = '.$source.'; return '.$value.';';
	if (($val_arr = eval($expr)))
		break;
}

/*
 * lucky:
 * ниже разбирали значение аргумента each
 *
 * плохо это, что плагин занимается тем, что должен
 * делать шаблонный движок.. я прав?
 *
 * ну т.е., если нам приходит нечто, в виде имени шаблонной переменной,
 * то извлекать от туда данные должен сам шаблонный движок.
 *
 * в прочем, передача этой обязанности шаблонизатору,
 * ставит нас в зависимость от эффективности компилятора шаблонов.
 *
 * так что палка о двух концах.
 *
 * есть три идеи:
 *		1. отказаться от использования компилятора в плагинах.
 *			и разбирать аргументы вручную настолько эффективно, насколько это возможно.
 *			+ гибкость
 *			- эффективность
 *		2. сделать хинты в плагинах, касаемо типов аргументов, чтобы шаблонный движок сам разбирал
 *				очевидно, что можно инлайнить разбор аргументов в темплейт страницы -- все равно они, 
 *				в пределах шаблона, остаются неизменными.
 *		3. научить шаблонный движок делать плагины вставками в скомпилированные шаблоны страниц.
 *			иными словами -- inline'ить.
 * 
 * сейчас, плагины, от кеширования почти не выигрывают, потому что практически ничего не кешируют
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

// lucky@npj: разыменовываем object.attr.attr
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
	// пошли за фикстурами
	$v = include $rh->FindScript( 'fixtures', $key );
	// lucky@npj: разыменовываем object.attr.attr
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
	// надо чтобы его могло и не быть

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
