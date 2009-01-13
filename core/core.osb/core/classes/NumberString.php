<?

/*

	NumberString -- для человеческий надписей после цифр, типа "10 зайцев" вместо "10 зайцы"

	---------------

  * String ( $number, $string )  -- возвращает $string в падеже, соответствующем числу $number
    - $number -- число, для которого просклонять слово
    - $string -- слово, которое нужно просклонять, в именительном падеже

  * var $STRING -- хэш [слово=>падежи], список склонений для заданных слов

=============================================================== v.1 (Zharik)
*/

class NumberString{
	
	var $STRINGS = array(
/*		"вопрос"=>array("вопрос","вопросов","вопроса"),
		"запчасть"=>array("запчасть","запчастей","запчасти"),
		"модель"=>array("модель","моделей","модели"),
		"штука"=>array("штука","штук","штуки"),*/
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