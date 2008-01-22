<?php
	
	ob_start("ob_gzhandler");
	
	include("../core/classes/RequestHandler.php");
	
	//Инициализация 
	$rh =& new RequestHandler("config/config.php");
	
  //обработка запроса, выполнение хэндлера
	$rh->ProceedRequest();
	
  //заканчиваем работу
	$rh->End();  
	
	ob_end_flush();
	
?>
