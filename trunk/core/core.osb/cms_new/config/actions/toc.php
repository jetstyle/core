<?
  
function action_toc( &$rh, $PARAMS ){

	$rh->UseClass('Toc');
	$toc =& new Toc( &$rh );
		
	return $toc->correct( $PARAMS['__string'] );
}
?>