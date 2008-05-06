<?php
include( $rh->FindScript('handlers','_start') );

//не авторизован?
if( !$prp->IsAuth() )
{
	$rh->redirect( $rh->url.'login' );
}
	
	$orders = explode(",",$rh->getVar('order')) ;
    $table = $rh->getVar('table');
    
    if (!empty($orders) && !empty($table))
    foreach ($orders as $i=>$order)
    {
        $out .= "$order = $i \n\r";
        $sql = "UPDATE ".$table." SET _order=".$db->quote($i)." WHERE id=".$db->quote($order);
        $db->query($sql);
    }
    die($out);
?>