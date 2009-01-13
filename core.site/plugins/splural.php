<?php

/**		
 * Плагин splural -- печатает форму слова, связанную с числовым значением
 *
 *
 * шаблон
 *	{{!splural *v do=@:t}}
 *		  HINT: шаблон м.б. условным:
 *				{{:t Text.singualr}}пень{{/:t}}
 *				{{:t Text.dual	   }}пня{{/:t}}
 *				{{:t Text.plural  }}пней{{/:t}}
 *
 *	2 или 3 полных слов
 *	{{!splural *v "пень" "пня" "пней"}}
 *
 *	суффиксная форма
 *	{{!splural *v "п" "ень" "ня" "ней"}}
 *
 */

$value = $params['value']?$params['value']:$params[0]; // ключ

$s = array();

$d1 = $value % 10; // последний занк
$d2 = ($value / 10) % 10; // предпоследний знак

if ($d2 == 1) // 10, 11, 12, .. 19
{
	$s['plural'] = True;
	$s['form'] = 3;
}
else
{
	if ($d1 == 1)
	{
		$s['singular'] = True;
		$s['form'] = 1;
	}
	else
	if ($d1 == 2 || $d1 == 3 || $d1 == 4)
	{
		$s['dual'] = True;
		$s['form'] = 2;
	}
	else // 5, 6, 7, 8, 9, 0
	{
		$s['plural'] = True;
		$s['form'] = 3;
	}
}

$template_name = $params['do']?$params['do']:$params['use']; // шаблон

if (isset($template_name))
// параметры: шаблон форма
{
	$old_s =& $tpl->get("Text");
	$tpl->setRef("Text", $s);
	$out = $tpl->parse(substr($template_name, 1));
	$tpl->setRef("Text", $old_s );
}
else
if (isset($params[4])) 
// параметры: суффиксная форма
{
	$root = $params[1];
	switch ($s['form'])
	{
	case 1: $out = $root.$params[2]; break;
	case 2: $out = $root.$params[3]; break;
	case 3: $out = $root.(isset($params[4]) ? $params[4] : $params[3]); break;
	}
}
else						  
// параметры: полнотекстовая форма
{
	switch ($s['form'])
	{
	case 1: $out = $params[1]; break;
	case 2: $out = $params[2]; break;
	case 3: $out = isset($params[3]) ? $params[3] : $params[2];
	}
}

echo $out;

?>
