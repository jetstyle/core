<?php
//������ ������
if( $rh->render_toolbar )
{
	$this->useClass('Toolbar');
	$toolbar = new Toolbar($this);
	$toolbar->handle();
}
?>