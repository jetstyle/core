<?php
/*
  Оборачивает содержимое в системную обёртку. 
*/
  //генерируем уникальный ID для обёртки
  $wid = $params['id'];
  if( $params['var'] )
    $wid = $rh->tpl->get($params['var']);
  while( !$wid || isset($rh->WIDS[$wid]) )
  {
    $wid = rand(1,1000000);
  }
  $rh->WIDS[$wid] = true;
  
  $rh->tpl->set('_content',$params['_']);
  $rh->tpl->set('_id',$wid);

  $rh->tpl->set('_title', $params['tvar'] ? $rh->tpl->get($params['tvar']) : $params['title'] );

  $vis = $params["cookie"]!="off" && isset($_COOKIE["c".$wid]) ? !$_COOKIE["c".$wid] : !$params["closed"];
  $rh->tpl->set('_class_name_1', $vis ? "visible" : "invisible" );
  $rh->tpl->set('_class_name_2', !$vis ? "visible" : "invisible" );
  
  return $rh->tpl->parse( 'wrapper.html' );
?>