<?

/*

	NumberString -- ��� ������������ �������� ����� ����, ���� "10 ������" ������ "10 �����"

	---------------

  * String ( $number, $string )  -- ���������� $string � ������, ��������������� ����� $number
    - $number -- �����, ��� �������� ����������� �����
    - $string -- �����, ������� ����� �����������, � ������������ ������

  * var $STRING -- ��� [�����=>������], ������ ��������� ��� �������� ����

=============================================================== v.1 (Zharik)
*/

class NumberString{
	
	var $STRINGS = array(
/*		"������"=>array("������","��������","�������"),
		"��������"=>array("��������","���������","��������"),
		"������"=>array("������","�������","������"),
		"�����"=>array("�����","����","�����"),*/
	);
	
	function String($number,$string){
		$n = $number%100;
		if($n>=11 && $n<=20 ) return $this->STRINGS[$string][1];
		else{
			$n = $n%10;
			if($n==1) return $this->STRINGS[$string][0];
			else if($n==2 || $n==3 || $n==4 ) return $this->STRINGS[$string][2];
			else return $this->STRINGS[$string][1];
		}
	}
}

?>