<?
/*
   StateSet ( &$rh ) -- ����� ��� ������ � "�����������"
   			"���������" ����������� �������� ����������-��������
	   	$rh -- ������ �� $rh

  ---------
  * Set ( $key, $val='' ) -- ��������� ���������� (����������) ���������
  		$kay -- ��������� ��������� ���������:
  			1. ��� ���������� ��������� - ��������� ���������� ������������� �������� $val
  			2. ������ ����������-��������, �������� �������� ����������� ���������� ���������, � ��� �� "���������"
  			3. ������ ������ StateSet, � �������� ����������� �������� ���������� � "���������"
  			� �2 � �3 ������ �������� ������ ������������
  		$val -- �������� ����������

  * _Set ( $key, &$val ) -- � ���������� ��������� ������� ������ �� ������
  		$key -- ��� ���������� ���������
  		&$val -- ������ �� ������

	* &Get( $key ) -- ���������� ������ �� �������� ���������� ���������
			$key -- ��� ���������� ��������

  * Free ( $key='' ) -- �������� �������� ���������� ���������
  		$key -- ��������� ��������� ���������:
  			1. ��� ���������� ��������� - ���������� ��������� ����������
  			2. ������ ���������� ��������� - ���������� ��� ���������� �� ������
  			3. ������ �������� - ���������� ��� ���������� ���������

	* State ( $method=0, $_SKIP=false, $all=false ) -- ����������� ���������
				���� ����������� ���� ���, gthtajhvbhjdsdftncz ������ ����� ��������� ���������
			$method -- 0 - ����������� ��� get-������, ����������, ��������� � "���������", � ���� �� �����������
								 1 - ����������� ��� ����� hidden-����� ��� �����
			$_SKIP -- ������ ���� ���������� ���������, ������� �� ����� �������� � ����
			$all -- ���� true, �� � ���� ���������� ��� ����������, ���������� �� "���������"

	* StateAll ( $method=0 ) -- ����������� ��������� ��������� �������, ���������� �� "���������"
				�� ����� ����, �������� State($methos,array(),true) 

  * StatePlus( $method=0, $_VALUES ) -- ����������� ��������� ��������� �������,
	  		��������� ���� ���������� �� $_VALUES
  		$_VALUES -- ������ ����������-��������	

	* Keep( $var_name, $type='' ) -- ��������� �������� ��������� ���������� ����� $rh->GetVar(),
				��������� �������� � ���������� ��������� � ��� �� ������
			$var_name -- ��� ���������� 
			$type -- ��� ����������, ����� ��� ������ $rh->GetVar()

=============================================================== v.1 (Zharik)
*/
	
class StateSet {

	var $rh;
	
	var $VALUES = array();	//������ ����������-��������, � ������� �������� ���������
	var $modified = 0;	//���� �� ��������� ��������������� � ������� �������� ���������� �����
	
	var $get_state;	//���� � ������� get_������
	var $post_state; //���� � ������� hidden-�����
	var $GET_FREEZED = array(); //�.�. "���������" - ��� �� �������� ��� ������������ get-�����
	
	var $amp_xml = '&amp;';
	var $amp_get = '&';
	var $amp_mode = 'xml';

	function StateSet(&$rh){
		$this->rh =& $rh;
	}
	
	function Set($key,$val=''){
    if(is_array($key)) $this->VALUES = array_merge($this->VALUES,$key);
    else if(is_a($key,'StateSet')){
			$this->VALUES = array_merge( $this->VALUES, $key->VALUES );
			$this->GET_FREEZED = array_merge( $this->GET_FREEZED, $key->GET_FREEZED );
		} else $this->VALUES[$key] = $val;
		$this->modified = true;
	}
	
	function _Set($key,&$val){
    if(is_array($key)) $this->VALUES = array_merge($this->VALUES,$key);
    else $this->VALUES[$key] =& $val;
		$this->modified = 1;
	}
	
	function &Get($key){
		return $this->VALUES[$key];
	}
	
	function Free($key=''){
		if($key=='') $this->VALUES = array();
		else if(is_array($key))
			for($i=0;$i<count($key);$i++) unset($this->VALUES[$key[$i]]);
			else unset($this->VALUES[$key]);
		$this->modified = true;
	}
	
  function State( $method=0, $_SKIP=false, $all=false ){
  	if($this->modified || $_SKIP!==false ){
			if($_SKIP===false) $_SKIP = array();
  		$this->get_state = $this->post_state = '';
      foreach($this->VALUES as $k=>$v){
      	if($v!='' && in_array($k,$_SKIP)!==true){
	      	if( $all || !$this->GET_FREEZED[$k] )
						$this->get_state .= $k.'='.$v.( $this->amp_mode=='xml' ? $this->amp_xml : $this->amp_get );
  	    	$this->post_state .= "<input type='hidden' name='".$k."' value='".$v."'>\n";      
    	  }
			}
      $this->modified = false;
  	}
  	return ($method)? $this->post_state : $this->get_state;
  }

  function StateAll( $method=0 ){
  	return $this->State( $methos, array(), true );
  }
	
  function StatePlus($method=0,$_VALUES){
  	//add values
  	if(is_array($_VALUES)){
  		$this->VALUES = array_merge($this->VALUES,$_VALUES);
			$this->modified = true;
  	}  	
  	//generate state strings
  	$str = $this->State($method);
  	//remove values
  	if(is_array($_VALUES)){
  		$this->Free(array_keys($_VALUES));
  	}  	
  	return $str;
  }
	
	function Keep($var_name,$type=''){
		$var = $this->rh->GetVar($var_name,$type);
		$this->Set($var_name,$var);
		return $var;
	}

	/*
	��������� �� �������� �� ����������������.
	
	function Unpack(&$str,$keep=false){
		$str = str_replace('&amp;','&',$str);
		$t1 = explode("&",$str);
		$this->VALUES = array();
		for($i=0;$i<count($t1);$i++){
			$t2 = explode('=',$t1[$i]);
			if($t2[0][0]!='_' || $keep) $this->VALUES[$t2[0]] = $t2[1];
		}
	}
	
	function Pack_getstr( $_SKIP=array() ){
		$str = $this->State(0,$_SKIP);
		$str = str_replace('&amp;','*AMP*',$str);
		$str = str_replace('=','*EQ*',$str);
		return $str;
	}
	
	function Unpack_getstr($str,$fast=false){
		$str = str_replace('*AMP*','&amp;',$str);
		$str = str_replace('*EQ*','=',$str);
		if(!$fast) $this->Unpack($str);
		else return $str;
	}
	*/

}
	
?>