<?php
/*
		DebugDummy - ��������� ��������� ��������� ������ Debug, �� ��� ���� �� ������ ������.
		������������ ��� ����, ��� �� �� ����� ����������� �����, ����� ��� �� �����, 
		� ��� �� ��� ���� ��������� ��� ��������� ��������.
		
=============================================================== v.1 (Zharik)
*/

class DebugDummy {
	
	var $error;
	
  function Flush(){}
  function _getmicrotime(){}
  function Milestone(){}
  function Trace(){}
  function Trace_R(){}
  function IsError(){}
	
  function Error($msg){
     $this->error =  "<span style='font-weight:bold; color:#ff4000;'>[ERROR] ".$msg."</span><br />";
		 $this->Halt();
	}
  function Halt(){
     header("Content-Type: text/html; charset=windows-1251");
		 echo $this->error;
     die("prematurely dying.");
	} 
  function ErrorHandler(){}
	
// EOC{ Debug } 
}

?>
