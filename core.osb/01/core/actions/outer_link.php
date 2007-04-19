<?
function action_outer_link( &$rh, &$PARAMS ){
	
	$re = "/(\<a.*?href=['\"\s]{0,1}http\:\/\/.*?\>)/i";
	$rep = "\\1<img src='".( $rh->front_end->path_rel ? $rh->front_end->path_rel : $rh->path_rel )."images/siteicon/link.gif' valign='bottom'>";
	
	return preg_replace( $re, $rep, $PARAMS['__string'] );
}

?>