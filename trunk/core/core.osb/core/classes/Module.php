<?
/*
	Module -- �������� ��� ������ ������������� ������

	---------

  * Module ( &$rh ) -- ����������� �����������

  * InitInstance () -- �����������������, ����������� � �����������

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