<?
function action_js( &$rh, $PARAMS ){
  
  //подгрузка отдельного скрипта
  if ($file = $PARAMS["file"] ) {
    if(!$rh->JS[$file]){
      $rh->JS[$file] = true;
      $str = "<script type=\"text/javascript\" src=\"".$rh->path_rel."js/".$file.".js\"></script>";
      $rh->tpl->Assign("html_head",$str,true);
    }
  }
  
  //body.onLoad
  if ($onLoad = $PARAMS["onload"] ) {
    $rh->tpl->assign( "html_onload", $onLoad, true );
  }
}

?>