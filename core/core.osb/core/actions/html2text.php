<?

function action_html2text( &$rh, &$PARAMS ){

	$text = $PARAMS['__string'];
	
  $text = str_replace("\n", " ", $text);
  $text = str_replace("\r", " ", $text);

  $nohtml = preg_replace("/<br.*?>/i", "\r\n", 
  					preg_replace("/<(h\d)>(.*?)<\/\\1>/i", "\r\n-=[\\2]=-\r\n", 
  					preg_replace("/<p.*?>/i", "\r\n", 
  					preg_replace("/<\/p.*?>/i", "\r\n", 
            preg_replace("/<hr.*?>/i", "\r\n----------------------------\r\n", 
            preg_replace("/^\s+/im", "", 
            preg_replace("/\s+/i", " ", 
            str_replace("<li>", "<br>  *  ", 
            preg_replace( '/&.*?;/', '#', 
            preg_replace( '/<a(.*?)href=(\"|\'|)([^\"\' ]*)([^>]*)>(.*?)<\/a>/i', '$5 ( $3 )', 
            preg_replace( '/<a([^>]*)><img([^>]*)><\/a>/i', '', 
            preg_replace( '/<style>.*?<\/style>/i', '', 
            preg_replace( '/&(quot|laquo|raquo|\#0?147|\#0?148);/', '"', //"
            preg_replace( '/&(ndash|\#0?150);/', '-', 
            preg_replace( '/&(mdash|\#0?151);/', '--', 
            preg_replace( '/&nbsp;/', ' ', 
              $text 
            ))))))))))))))));

  $nohtml = preg_replace( "/<[^>]+>/i", "", $nohtml );
  $nohtml = preg_replace( '/([^ ]+) \( \1 \)/i', '$1', $nohtml ); // delete http://npj.ru ( http://npj.ru )

  return $nohtml;
}

?>