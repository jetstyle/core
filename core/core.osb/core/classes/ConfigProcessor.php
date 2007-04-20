<?php
/*
	ConfigProcessor -- �������� ��� RequestHandler, � �� ������� ���������������� ������ � ���������
	---------

  * _FindScript( $type, $name, $level, $direction ) -- ��� ����������� �������������
		  		���� ������ ���� $type � ������ $name � ���������� ������ ���� � ������ ������
			  	����� ���������� � ������ $level, � ������ ������� ������ � ����������� $direction
      - $type -- ��� ����������, � ������� ������ ������, ��� '/' �� �����
      - $name -- ��� ��� ������ ��� '.php' �� �����
      - $level -- �������, �� ������� ������. 0 - ����, 1 - �������� ����, 2 - ������ ����
      - $direction -- �����������, � ������� ���������� ������ ������ � ������ �������, �.�. +1 ��� -1.

  * FindScript( $type, $name, $level, $direction=SEARCH_DOWN ) -- ������ ��� _FindScript()
		  		��������� $type �� ������������
		  		��������� ���������� ��� � _FindScript()

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