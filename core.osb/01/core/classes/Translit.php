<?php
/*
  Translit PHP class.
  v.1.0
  24 October 2004

  Zharik: Поскольку мне нужен только UrlTranslit, остальные функции я выкинул.

  Подробнее о полной версии: http://pixel-apes.com/translit

  ---------

  Транслитерация ссылок (приведение их в соответствие с форматом URL).
  Латинские буквы и цифры остаются, а русские + знаки препинания преобразуются
  одним из способов (способы нужны каждый для своей задачи)

  ---------

  * UrlTranslit( $string, $allow_slashes = TR_NO_SLASHES ) 
    -- преобразовать строку в "красивый читаемый URL"

  * во всех функциях параметр $allow_slashes управляет тем, игнорировать ли символ "/",
    пропуская его неисправленным, либо удалять его из строки

	* TranslateLink($str,$cnt=20)
		-- ограничить $str таким числом слов, что бы общая длина не сильно превышала $cnt

=============================================================== (Zharik)

*/

define("TR_ENCODE", 0);
define("TR_DECODE", 1);
define("TR_NO_SLASHES", 0);
define("TR_ALLOW_SLASHES", 1);

class Translit
{

  //пустой конструктор, чтобы методы могли работать через ::
  function Translit() {}

  //URL transliterating
  function UrlTranslit($string, $allow_slashes = TR_NO_SLASHES)
  {
   $slash = "";
   if ($allow_slashes) $slash = "\/";

   static $LettersFrom = "абвгдезиклмнопрстуфыэйхё";
   static $LettersTo   = "abvgdeziklmnoprstufyejxe";
   static $Consonant = "бвгджзйклмнпрстфхцчшщ";
   static $Vowel = "аеёиоуыэюя";
   static $BiLetters = array( 
     "ж" => "zh", "ц"=>"ts", "ч" => "ch", 
     "ш" => "sh", "щ" => "sch", "ю" => "ju", "я" => "ja",
   );

   $string = preg_replace("/[_\s\.,?!\[\](){}]+/", "_", $string);
   $string = preg_replace("/-{2,}/", "--", $string);
   $string = preg_replace("/_-+_/", "--", $string);
   $string = preg_replace("/[_\-]+$/", "", $string);
   
   $string = strtolower( $string );
   //here we replace ъ/ь 
   $string = preg_replace("/(ь|ъ)([".$Vowel."])/", "j\\2", $string);
   $string = preg_replace("/(ь|ъ)/", "", $string);
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