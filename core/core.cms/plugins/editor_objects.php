<?php
if(!isset($rh->editorObjectsCorrector))
{
	$rh->useClass('EditorObjectsCorrector');
	$rh->editorObjectsCorrector = new EditorObjectsCorrector( &$rh );
}	

return $rh->editorObjectsCorrector->correct( $params );
?>