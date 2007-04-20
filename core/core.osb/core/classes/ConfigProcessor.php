<?php
/*
	ConfigProcessor -- прототип для RequestHandler, в нём собрана функциональность работы с конфигами
	---------

  * _FindScript( $type, $name, $level, $direction ) -- для внутреннего использования
		  		ищет скрипт типа $type с именем $name и возвращает полный путь в случае успеха
			  	поиск начинается с уровня $level, в случае неудачи ищется в направлении $direction
      - $type -- имя директории, в которой искать скрипт, без '/' на конце
      - $name -- имя имя файлаб без '.php' на конце
      - $level -- уровень, на котором искать. 0 - ядро, 1 - типичный сайт, 2 - данный сайт
      - $direction -- направление, в котором перебирать другие уровни в случае неудачи, д.б. +1 или -1.

  * FindScript( $type, $name, $level, $direction=SEARCH_DOWN ) -- обёртка для _FindScript()
		  		проверяет $type на допустимость
		  		семантика переменных как в _FindScript()

=============================================================== v.1 (Zharik)
*/

define( "SEARCH_DOWN"	, -1 );
define( "SEARCH_UP"		, 1 );

class ConfigProcessor{

	var $DIRS = array();
	var $missed_OK = false;
	var $check_run_type = false;

	function ConfigProcessor(){
		if(!defined("CURRENT_LEVEL")) define( "CURRENT_LEVEL", 0 );
	}
	
	function EndError($str=""){
		die("<font color='red'><b>ConfigProcessor error</b>: ".$str."</font>");
	}
	
	function FindScript( $type, $name, $level=CURRENT_LEVEL, $direction=SEARCH_DOWN, $has_ext=false )
    {
		
		if( !$this->missed_OK && $name=="" ) $this->EndError("ConfigProcessor::_FindScript - script name is empty, \$type=$type");
		
		if( !isset($this->DIRS[$type]) ) $this->EndError("Script type \"<b>$type</b>\" is not defined.");
		
		$ARR = $this->DIRS[$type];
		$count_ARR = count($ARR);
		
		$ext = $has_ext ? '' : '.php';

        if ($level >= $count_ARR)
            $level = $count_ARR-1;
        
		for( $i=$level; $i>=0 && $i<$count_ARR; $i+=$direction )
        {
			$fname = $ARR[$i].$name.$ext;
/*
	        if ($type=="handlers")
            {
                echo '<hr>'.$fname;
            }
*/
//			echo $fname.'<br>\n';
			if( @file_exists($fname) && !@is_dir($fname) ) break;
			else $fname = "";
		}
		
		if( $fname=="" )
        {
            /*
            echo '<pre>';
            var_dump(debug_backtrace());
            echo '</pre>';
            */
			if(!$this->missed_OK) $this->EndError("Script not found: type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, direction=<b>$direction</b>");
			else return false;
		}else return $fname;
	
	}
	
}


?>