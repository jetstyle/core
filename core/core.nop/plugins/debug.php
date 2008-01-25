<?php
/**
 * @author lunatic
 * @created 09.09.2007
 **/

if($rh->enable_debug)	
{
	echo '<div style="clear: both; font-size: 14px;">Total Queries: <b>'.$rh->db->queryCount.'</b></div>';
	echo Debug::getHtml();
} 
?>