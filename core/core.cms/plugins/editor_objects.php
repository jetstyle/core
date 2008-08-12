<?php
if(!isset($rh->editorObjectsCorrector))
{
	Finder::useClass('EditorObjectsCorrector');
	$rh->editorObjectsCorrector = new EditorObjectsCorrector( &$rh );
}

return $rh->editorObjectsCorrector->correct( $params );
?>