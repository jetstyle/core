<?php
  error_reporting (E_ALL ^ E_NOTICE);

  ob_start("ob_gzhandler");
  
  include(dirname(__FILE__)."/../libs/core.osb/core/classes/RequestHandler.php");
  
  //������������� 
  $rh =& new RequestHandler("config/config.php");
  
  //��������� �������, ���������� ��������
  $rh->ProceedRequest();
  
  //����������� ������
  $rh->End();  
  
  ob_end_flush();
  
?>
