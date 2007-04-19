<?
function action_css( &$rh, $PARAMS ){
	
	$file = $PARAMS['file'];

	if(!$rh->CSS[$file]){
		$rh->CSS[$file] = true;
		$str = "<link rel=\"stylesheet\" href=\"".$rh->path_rel.'css/'.$file.".css\" type=\"text/css\" />";
		$rh->tpl->Assign('html_head',$str,true);
	}
}

?>