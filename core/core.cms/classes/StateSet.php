<?
/*
   StateSet ( &$rh ) -- класс для работы с "состояниями"
   			"состояние" описывается массивом переменная-значение
	   	$rh -- ссылка на $rh

  ---------
  * Set ( $key, $val='' ) -- установка переменной (переменных) состояния
  		$kay -- несколько возможных вариантов:
  			1. имя переменной состояния - указанной переменной присваивается значение $val
  			2. массив переменная-значения, согласно которому заполняются переменные состояния, а так же "заморозка"
  			3. объект класса StateSet, с которого считываются значения переменных и "заморозка"
  			в п2 и п3 второй параметр метода игнорируется
  		$val -- значение переменной

  * _Set ( $key, &$val ) -- в переменную состояния кладётся ссылка на объект
  		$key -- имя переменной состояния
  		&$val -- ссылка на объект

	* &Get( $key ) -- возвращает ссылку на значение переменной состояния
			$key -- имя переменной сотояния

  * Free ( $key='' ) -- обнуляет значения переменных состояния
  		$key -- несколько возможных вариантов:
  			1. имя переменной состояния - обнуляется указанная переменная
  			2. массив переменных состояния - обнуляются все переменные из списка
  			3. пустое значение - обнуляются все переменные состояния

	* State ( $method=0, $_SKIP=false, $all=false ) -- сериализует состояние
				дамп формируется один раз, gthtajhvbhjdsdftncz только после изменения состояния
			$method -- 0 - сериализует как get-запрос, переменные, указанные в "заморозке", в дамп не вкллючаются
								 1 - сериализует как набор hidden-полей для формы
			$_SKIP -- массив имен переменных состояния, которые не нужно включать в дамп
			$all -- если true, то в дамп включаются все переменные, назависимо от "заморозки"

	* StateAll ( $method=0 ) -- сериализует состояние указанным методом, независимо от "заморозки"
				на самом деле, вызывает State($methos,array(),true) 

  * StatePlus( $method=0, $_VALUES ) -- сериализует состояние указанным методом,
	  		дополняет дамп значениями из $_VALUES
  		$_VALUES -- массив переменная-значение	

	* Keep( $var_name, $type='' ) -- вычисляет значение указанной переменной через $rh->GetVar(),
				сохраняет значение в переменной состояния с тем же именем
			$var_name -- имя переменной 
			$type -- тип переменной, нужен для вызова $rh->GetVar()

=============================================================== v.1 (Zharik)
*/
	
class StateSet {

	var $rh;
	
	var $VALUES = array();	//массив переменная-значения, в котором хранится состояние
	var $modified = 0;	//было ли состояние модифицированно с момента создания последнего дампа
	
	var $get_state;	//дамп в формате get_строки
	var $post_state; //дамп в формате hidden-полей
	var $GET_FREEZED = array(); //т.н. "заморозка" - что не выводить при формировании get-дампа
	
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
	Кандидаты на удаление по неиспользуемости.
	
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