<?php
	
	ob_start("ob_gzhandler");
	
	include("../core/classes/RequestHandler.php");
	
	//������������� 
	$rh =& new RequestHandler("config/config.php");
	
  //��������� �������, ���������� ��������
	$rh->ProceedRequest();
	
  //����������� ������
	$rh->End();  
	
	ob_end_flush();
	
?>
