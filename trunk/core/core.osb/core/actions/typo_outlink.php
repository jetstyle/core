<?php

function action_typo_outlink( &$rh, &$PARAMS ){
  
	if( !isset($rh->typo) ){
		$rh->UseClass('Typografica');
		$rh->typo =& new Typografica( &$rh );
		$rh->typo->settings["dashglue"] = false;
		$rh->typo->settings["dashwbr"] = true;
	}	
  
  //подсвечиваем внешние ссылки
  //\<a\s+href=(['\"]{0,1})(http\:\/\/(?!"..")).*?(?:\1|\s).*?\>
  $__href = str_replace('http://','',$rh->url);
  if($__href[strlen($__href)-1]=='/')
    $__href = substr($__href,0,strlen($__href)-1);
  $_href = str_replace(
    '.','\.',
    str_replace(
      '/','\/',
      $__href
    )
  );
//  die($_href);
  $text = $PARAMS['__string'];
  if( substr($__href,0,3)=='www' )
    $text = str_replace("http://".substr($__href,0,3),"http://".$__href,$text);
  $text = preg_replace("/<a\s+([^>]*?href=(['\"]{0,1})http\:\/\/(?!".$_href.").*?(?:\\2|\s|\>).*?)\>([A-Za-zА-Яа-я0-9\.\,\-\s\:\!\?\&\;]+?)<\/a>/i","<img src=\"".$rh->front_end->path_rel."images/outlink.gif\" class=\"outlink\" hspace=\"2\"><a \\1>\\3</a>",$text);
//  $text = $PARAMS['__string'];
//  die('111');
  
	return $rh->typo->correct( $text, false );
}

?>