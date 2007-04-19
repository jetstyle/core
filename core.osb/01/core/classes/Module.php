<?
/*
	Module -- прототип для класса представления модуля

	---------

  * Module ( &$rh ) -- примитивный конструктор

  * InitInstance () -- псевдоконструктор, перегружать в наследниках

=============================================================== v.1 (Zharik)
*/
	
	
class Module {
	
  var $rh;
	
  function Module(&$rh){
  	$this->rh =& $rh;
  }
	
  function InitInstance(){
  	//override this
  }
}

?>