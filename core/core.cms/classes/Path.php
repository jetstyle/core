<?
/*
   Path(&$rh,$path_str='') -- ���������� ����� ��������� ������ ����
   		$rh -- ������ �� $rh
   		$path_str -- ������ ����, ���� ������, �� ����� ����������� Handle()

   		� ������ ������, ���� page �� ������� ����, �������� ��� ������ ���� ��� page

  ---------
  * Handle($path_str) -- ������������ ������ ����, ��������� �������������� �����
  											���������� ���������� ������ � $rh->GLOBALS
   		$path_str -- ������ ����, ����������� ������ ���� �� ���������� ��������� ���������� $rh
  
=============================================================== v.1 (Zharik)
*/

class Path {
	
	var $rh; //������ �� $rh
	var $path_orig; //����������� ������ ����
	var $path_trail; //�������������� ������ ����
	
	function Path(&$rh,$path_str=''){
		$this->rh =& $rh;
		if( $path_str!='' )
			$this->Handle($path_str);
	}
	
  //�������� �������� �� �������������
	function _CheckFname($fname,$path_str){
		$rh =& $this->rh;
		$rh->missed_OK = true;
		if( $rh->FindScript('handlers',$fname) ){	
			$rh->GLOBALS['page'] = $fname;
			if(strlen($path_str) > strlen($fname) )
				$this->path_trail = str_replace( $fname.'/', '', $path_str );
			$_found = true;
		}
		$rh->missed_OK = false;
		return $_found;
	}
	
	function Handle($path_str){
		$rh =& $this->rh;
		$this->path_orig = $path_str;
		if(!$rh->GLOBALS['page']){
//			$path = str_replace( $rh->path_rel, '', $path_str );
			$B = explode( '/', rtrim($path_str) );
  		//��������� ��������
      //� ����� � � ���������
  		$_found = $this->_CheckFName($B[0],$path_str);
  		if(!$_found)
  			$_found = $this->_CheckFName($B[0].'/'.$B[1],$path_str);
/*			$path = str_replace( $rh->path_rel, '', $path_str );
			$B = explode( '/', $path );
			$rh->GLOBALS['page'] = $B[0];
			$this->path_trail = str_replace( $B[0].( count($B) > 1 ? '/' : '' ), '', $path );*/
		}
	}
}	
?>