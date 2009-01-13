<?php
/*
 Оборачивает содержимое в системную обёртку.
 */
//генерируем уникальный ID для обёртки
$wid = $params['id'];

while( !$wid || isset($tpl->WIDS[$wid]) )
{
	$wid = rand(1,1000000);
}
$tpl->WIDS[$wid] = true;

$tpl->set('_content',$params['_']);
$tpl->set('_id',$wid);

$tpl->set('_title', $params['tvar'] ? $tpl->get($params['tvar']) : $params['title'] );

$vis = $params["cookie"]!="off" && isset($_COOKIE["c".$wid]) ? !$_COOKIE["c".$wid] : !$params["closed"];
$tpl->set('_class_name_1', $vis ? "visible" : "invisible" );
$tpl->set('_class_name_2', !$vis ? "visible" : "invisible" );

return $tpl->parse( 'wrapper.html' );
?>