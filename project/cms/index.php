<?php
  error_reporting (E_ALL ^ E_NOTICE);

  ob_start("ob_gzhandler");
  
  include(dirname(__FILE__)."/../libs/core.osb/core/classes/RequestHandler.php");
  
  //Инициализация 
  $rh =& new RequestHandler("config/config.php");
  
  //обработка запроса, выполнение хэндлера
  $rh->ProceedRequest();
  
  //заканчиваем работу
  $rh->End();  
  
  ob_end_flush();
  
?>
