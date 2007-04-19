<?php
/*
  * ADODB_Error(...) - обработчик ошибок для ADOdb, 
				привязывать инструкциями типа $db->raiseErrorFn = "ADODB_Error";
	
=============================================================== v.1 (Zharik)
*/

function ADODB_Error( $db_type, $more, $error_no, $error_msg, $sql, $input_arr ){
  global $debug_hook;
  if (isset( $debug_hook )) {
    $debug_hook->Trace( "Executing sql: <b>$sql</b>" );
    $debug_hook->Error( "DBAL SQL Error {".$error_no."} ".$error_msg );
  }else echo "\n<p>DBAL SQL Error {".$error_no."} ".$error_msg."</p>\n";
}

?>