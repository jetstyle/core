<?
/*
	MessageSet -- ������ � �������-������: ��������, �������, ����������� ������
	---------
	
	* MessageSet( &$rh, $source=false ) - �����������
			- $rh - ������ �� $rh
			- $source - �������� ��� ��������
	
	* LoadFrom( &$source, $merge=false ) - �������� ������ �� ���������
			- $source - ������ �� ��������, ��������� ��������
				- [������] - �������������� ��� ���� � �����, � ������� ���������� ���� $VALUES['zhopa'] = 'pizda';
				- [������] - �������������� ��� ��� �����-��������
				- [������ ������ MessageSet] - ������ � ��� ->VALUES
			- $append - ���� true, �� ������������ �������� �� $VALUES �� ���������
	
	var $VALUES - ��� ����-������, � �� ������� ��
	
=============================================================== v.1 (Zharik)
*/

class MessageSet {
	
	var $rh;
	var $VALUES = array(); //��� ����-������
	var $debug_mode = false; //����� �������?
	
	function MessageSet( &$rh, $source=false ){
		$this->rh =& $rh;
		if( $source ) $this->LoadFrom($source);
	}
	
	function LoadFrom( $source, $merge=false ){
		if( is_string($source) ){
			//source is a file name 
			include($this->rh->FindScript("message_sets",$source));
			if($merge) $this->VALUES = array_merge( $this->VALUES, $VALUES );
			else $this->VALUES =& $VALUES;
			return;
		}
		if( is_array($source) )
			//source is a hash 
			$A =& $source;
		else if( get_class($source)==get_class($this) )
			//source is an object of the same class
			$A =& $source->VALUES;
		else
			//error
			$this->rh->debug->Error("MessageSet::Load - ������������ ��� \$source (".gettype($source).")");
		//record values
		if( $merge ) $this->VALUES = array_merge( $this->VALUES, $A );
		else $this->VALUES = $A;
	}
	
	function Get( $key, $return_key=false ){
		$str = $this->VALUES[$key];
		if( $this->debug_mode ){
			//�������������� ���������
			return ( $str=='' )? '<span style="background: #D73333;">'.$key.'</span>' : '<span style="background: #B5D733; "><b>['.$key.']</b> '.$str.'</span>';
		}else
			//���������� ������ ��� ����
			return ( $str=="" && $return_key )? $key : $str ;
	}
}
?>