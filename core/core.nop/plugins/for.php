<?php

/*		{{!for each=news do=test.html:news}}	     */
/* если массива news нету в шаблонном дамайне, то возьмём из фикстур :P */

$key = $params['each']?$params['each']:$params[0]; // ключ
//можно без each= а сразу, {{!for news do=test.html:news}}	     

$template_name = $params['do']?$params['do']:$params['use']; // ключ
//можно {{!for news use=test.html:news}}	     


$caller = $params['_caller'];
if ($template_name[0]==':')
	$template_name = $caller.'.html'.$template_name;   


if ( $key[0]=='*' )
{
	$key = substr($key, 1,strlen($key));
	$ref = $rh->tpl->Get("*");
	$val_arr = $ref[$key];
}
elseif ($key[0] == '#') // lucky@npj #object.attr
{
	$p = strpos($key, '.');
	$o = substr($key, 1,$p-1);
	$key = substr($key, $p+1);
	$ref = $rh->tpl->Get($o);
	$val_arr = $ref[$key];
}
else
{
	$keys = explode('.', $key);
	$c = $keys;
	$v =& $rh->tpl_data;
	while ($key = array_shift($keys))
	{
		if (isset($v[$key])) $v =& $v[$key];
		else { unset ($v); break; }
	}
	$val_arr =& $v;
	unset($v);

	if (!$val_arr){
		// пошли за фикстурами
		$val_arr = include $rh->FindScript( 'fixtures', $key );
	}

}

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
	foreach($val_arr AS $r)
	{
		if (is_array($r))
			$rh->tpl->SetRef('*', $r);
		else
			$rh->tpl->Set('_', $r);
		$content .= ($content ? $sep : '').$rh->tpl->parse($item_tpl);
	}

	echo $content;
	$content = '';
}

?>
