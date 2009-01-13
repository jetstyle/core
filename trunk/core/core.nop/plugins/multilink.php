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
	$href=trim($link);
    if (strpos($href,'http://')===false)
    {
        $href = "http://".$href;
    }
	$out[] = "<a href='".$href."'>".$link."</a>";
}

if ( !empty($out) )
{
    $out_str = implode(", ", $out);
    echo $out_str;
}

?>
