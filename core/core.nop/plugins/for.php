<?php

/*		{{!for each=news do=test.html:news}}	     */

$key = $params['each']?$params['each']:$params[0]; // ключ
//можно без each= а сразу, {{!for news do=test.html:news}}	     

$template_name = $params['do']?$params['do']:$params['use']; // ключ
//можно {{!for news use=test.html:news}}	     


$caller = $params['_caller'];
if ($template_name[0]==':')
   $template_name = $caller.'.html'.$template_name;   


if ( $key[0]=='*' ){
  $key = substr($key, 1,strlen($key));
  $ref = $rh->tpl->Get("*");
  $val_arr = $ref[$key];
}else{
  $val_arr = $rh->tpl_data[$key];
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