<?
	
	$pict = $rh->GetVar("pict");
	$title = urldecode($rh->GetVar("title"));
	
  //security
  $to_cut = array("./"=>"","http://"=>"","ftp://"=>"","home/"=>"");//,"/"=>""
//  $pict = strtr(strtolower($pict),$to_cut);
  foreach($to_cut as $tc=>$v)
    $pict = preg_replace( "/".preg_quote($tc,"/")."/i", "", $pict);

	$tpl->assign(array(
		"SRC"=> (@file_exists("files/".$pict))? $rh->back_end->path_rel."pict.php?img=files/".$pict : "images/0.gif",
		"TITLE"=>$title,
	));
	
	echo $tpl->parse("picture.html");
	
?>