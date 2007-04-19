<?php
/*
		DebugDummy - полностью повторяет интерфейс класса Debug, но при этом не делает ничего.
		Используется для того, что бы не вести логирование тогда, когда оно не нужно, 
		и что бы это было прозрачно для остальных скриптов.
		
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
