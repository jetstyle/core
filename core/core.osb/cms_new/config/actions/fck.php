<?
  
function action_fck( &$rh, $PARAMS ){

  include_once($rh->findScript('handlers', "FCKeditor/fckeditor"));
  extract($PARAMS);
  if(!$template) $template = 'forms/fck.html';
  
  $sBasePath = $_SERVER['PHP_SELF'] ;
  $sBasePath = substr( $sBasePath, 0, strpos( $sBasePath, "_samples" ) ) ;

  
  $oFCKeditor = new FCKeditor($rh->tpl->GetAssigned( $tpl_prefix ) . $input_name) ;
  $oFCKeditor->BasePath = $rh->path_rel.'FCKeditor/' ;  

  $oFCKeditor->Width  = '100%' ;
  $oFCKeditor->Height = '300' ;
  $oFCKeditor->Value = trim($__string) ? $__string : '<p>&nbsp;</p>';
  return "<div class='wrapper'>".$oFCKeditor->CreateHtml()."</div>";
}
?>