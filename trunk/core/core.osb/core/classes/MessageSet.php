<?
/*
	MessageSet -- работа с мессадж-сетами: загрузка, сли€ние, возвращение строки
	---------
	
	* MessageSet( &$rh, $source=false ) - конструктор
			- $rh - ссылка на $rh
			- $source - источник дл€ загрузки
	
	* LoadFrom( &$source, $merge=false ) - загрузка данных из источника
			- $source - ссылка на источник, возможные варианты
				- [строка] - обрабатываетс€ как путь к файлу, в котором инструкции типа $VALUES['zhopa'] = 'pizda';
				- [массив] - обрабатываетс€ как хэш ключь-значение
				- [объект класса MessageSet] - работа с его ->VALUES
			- $append - если true, то неперекрытые значени€ из $VALUES не удал€ютс€
	
	var $VALUES - хэш ключ-строка, в нЄм храница всЄ
	
=============================================================== v.1 (Zharik)
*/

class MessageSet {
	
	var $rh;
	var $VALUES = array(); //хэш ключ-строка
	var $debug_mode = false; //режим отладки?
	
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
			$this->rh->debug->Error("MessageSet::Load - некорректный тип \$source (".gettype($source).")");
		//record values
		if( $merge ) $this->VALUES = array_merge( $this->VALUES, $A );
		else $this->VALUES = $A;
	}
	
	function Get( $key, $return_key=false ){
		$str = $this->VALUES[$key];
		if( $this->debug_mode ){
			//дополнительна€ подсветка
			return ( $str=='' )? '<span style="background: #D73333;">'.$key.'</span>' : '<span style="background: #B5D733; "><b>['.$key.']</b> '.$str.'</span>';
		}else
			//возвращаем строку или ключ
			return ( $str=="" && $return_key )? $key : $str ;
	}
}
?>