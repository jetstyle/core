<?php
/*
  Translit PHP class.
  v.1.0
  24 October 2004

  Zharik: ��������� ��� ����� ������ UrlTranslit, ��������� ������� � �������.

  ��������� � ������ ������: http://pixel-apes.com/translit

  ---------

  �������������� ������ (���������� �� � ������������ � �������� URL).
  ��������� ����� � ����� ��������, � ������� + ����� ���������� �������������
  ����� �� �������� (������� ����� ������ ��� ����� ������)

  ---------

  * UrlTranslit( $string, $allow_slashes = TR_NO_SLASHES ) 
    -- ������������� ������ � "�������� �������� URL"

  * �� ���� �������� �������� $allow_slashes ��������� ���, ������������ �� ������ "/",
    ��������� ��� ��������������, ���� ������� ��� �� ������

	* TranslateLink($str,$cnt=20)
		-- ���������� $str ����� ������ ����, ��� �� ����� ����� �� ������ ��������� $cnt

=============================================================== (Zharik)

*/

define("TR_ENCODE", 0);
define("TR_DECODE", 1);
define("TR_NO_SLASHES", 0);
define("TR_ALLOW_SLASHES", 1);

class Translit
{

  //������ �����������, ����� ������ ����� �������� ����� ::
  function Translit() {}

  //URL transliterating
  function UrlTranslit($string, $allow_slashes = TR_NO_SLASHES)
  {
   $slash = "";
   if ($allow_slashes) $slash = "\/";

   static $LettersFrom = "������������������������";
   static $LettersTo   = "abvgdeziklmnoprstufyejxe";
   static $Consonant = "���������������������";
   static $Vowel = "���������";
   static $BiLetters = array( 
     "�" => "zh", "�"=>"ts", "�" => "ch", 
     "�" => "sh", "�" => "sch", "�" => "ju", "�" => "ja",
   );

   $string = preg_replace("/[_\s\.,?!\[\](){}]+/", "_", $string);
   $string = preg_replace("/-{2,}/", "--", $string);
   $string = preg_replace("/_-+_/", "--", $string);
   $string = preg_replace("/[_\-]+$/", "", $string);
   
   $string = strtolower( $string );
   //here we replace �/� 
   $string = preg_replace("/(�|�)([".$Vowel."])/", "j\\2", $string);
   $string = preg_replace("/(�|�)/", "", $string);
   //transliterating
   $string = strtr($string, $LettersFrom, $LettersTo );
   $string = strtr($string, $BiLetters );

   $string = preg_replace("/j{2,}/", "j", $string);

   $string = preg_replace("/[^".$slash."0-9a-z_\-]+/", "", $string);

   return $string;
  }

	function TranslateLink($str,$cnt=20){
		$str = $this->UrlTranslit($str);
		//collect words up to $cnt symbols
		$arr = explode("_",$str);
		$_str = "";
		for($i=0;$i<count($arr) && strlen($_str)<$cnt;$i++) $_str .= ($_str!="" ? "_" : "" ).$arr[$i];
		return $_str;
	}

}
?>