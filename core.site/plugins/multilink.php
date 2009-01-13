<?php

/**		
 * ссылки
 * nop
 */

$out = array();
$link_str = $params['_']  ? $params['_'] : $params[0]; // тип

$link_parts = preg_split("/\s|,/", $link_str);

foreach ($link_parts as $link)
{
	$link=trim($link);
	$out[] = "<a href='".$link."'>".$link."</a>";
}

if ( !empty($out) )
{
    $out_str = implode(", ", $out);
    echo $out_str;
}

?>