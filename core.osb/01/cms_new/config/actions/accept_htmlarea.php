<?
//âèäèìî, ıòî ôèëüòğ íå íóæåí. Ïîêà îñòàâëş, ÷òîá íå ìåíÿòü ñêğèïòû - âñ¸ ğàâíî òèïîãğàôèêó ïğèêğó÷èâàòü.
function action_accept_htmlarea( &$rh, &$PARAMS ){
	return $PARAMS['__string'];
	
	//ïğèáèâàåì ëèøíèå ñëåøè ïåğåä êàâû÷êàìè
//	$text = str_replace('\"','"',$PARAMS['__string']);
//	$text = str_replace("\'","'",$text);

  //äîáàâëÿåì ïğîïóùåííûå ïåğåãğàôû
//  $text = preg_replace("/\<\/p\>\s*([\w\d\.\,\-\:\;\'\"\'])/is","</p>\n<p>\\1",$text);

//	return $text;
}

?>