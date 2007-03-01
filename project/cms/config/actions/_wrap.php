<?
/*
  Оборачивает содержимое в системную обёртку. 
*/
function action_wrap( &$rh, &$PARAMS ){
  
  //генерируем уникальный ID для обёртки
  $wid = $PARAMS['id'];
  if( $PARAMS['var'] )
    $wid = $rh->tpl->GetValue($PARAMS['var']);
  while( !$wid || isset($rh->WIDS[$wid]) )
    $wid = rand(1,1000000);
  $rh->WIDS[$wid] = true;
  
  $rh->tpl->assign('_content',$PARAMS['__string']);
  $rh->tpl->assign('_id',$wid);
//  $rh->tpl->assign('_display',$_COOKIE['d'.$wid]);
//  $rh->tpl->assign('_sign', $_COOKIE['d'.$wid]=='none' ? '+' : '-' );
  $rh->tpl->assign('_title', $PARAMS['tvar'] ? $rh->tpl->GetValue($PARAMS['tvar']) : $PARAMS['title'] );

  $vis = $PARAMS["cookie"]!="off" && isset($_COOKIE["c".$wid]) ? !$_COOKIE["c".$wid] : !$PARAMS["closed"];
  $rh->tpl->assign('_class_name_1', $vis ? "visible ". ($PARAMS['subclass'] ? $PARAMS['subclass'] : '') : "invisible" );
  $rh->tpl->assign('_class_name_2', !$vis ? "visible ".($PARAMS['subclass'] ? $PARAMS['subclass'] : '') : "invisible" );
  
  if ($PARAMS['subclass'])
  	  $rh->tpl->assign('_subclass', $PARAMS['subclass']);
  	  
  
  return $rh->tpl->parse( $PARAMS['template'] ? $PARAMS['template'] : 'wrapper.html' );
}

?>