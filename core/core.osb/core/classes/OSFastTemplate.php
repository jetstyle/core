<?
/*

  OSFastTemplate -- ��������� ������. ����������� ������ 8))

  ------------------

  * OSFastTemplate ( $root_path , $cache_path, $SIGNATURES = array() ) -- �����������
    - $root_path -- ����������, ������� � ������� ������ �������
    - $cache_path -- ���������� ��� �������� ������������ ��������
    - $SIGNATURES -- �������������� ��������� (���� �� ������������)

  * _Load( $tpl_file, $full_path = false ) -- ��������, �������� � ����������� �������
          ��� ����������� �������������
    - $tpl_file -- ��������� ������� [���� � �����]:[���������]...
                  ���� � ����� (������������� ��� ���������� - ��. $full_path)
    - $full_path -- ���� false, �� ���� ������� ������ �� $root_path
                  ���� true, �� ���� ��������������� ��� ����������, 
                  ���������� ������� �� ����������
  
  * _Prepare( &$text )
          ��� ����������� �������������
          �������������� ����� ��� ������� _Parse(), 
          �������� ��������� ��������� �� �������� ��� explode
  
  * &_Parse ( $text, $store_to="", $append=false )
          ��� ����������� �������������
          ������ �������������� ����� (��. Prepare() ), ���������� ���������
    - $store_to -- ��� ���������� ����������, ���� ������ ���������, 
                  ���� �� �������, �� ������ �� �����������
    - $append -- ���� true, �� ��������� ����������� � ����������� ��������� ����������
                ���� false, �� ��������� �������������� ����������
  
  * &Parse ( $tpl, $store_to="", $append=false, $full_path=false )
            ������ ��������� ������, �������� _Parse()
            ���� ������ ��� �� ��������, ��������� Load()
    - $tpl -- ��������� �������
    - $full_path -- ��. Load()
  
  * &ParseFromString ( $string, $store_to="", $append=false )
            ������ ��������� ������, �������� _Parse()
    - $string -- ������ �� ������-��������
  
  * Assign( $assign_map, $value="", $append=false ) -- ����������� �������� ���������� (����������)
      $assign_map -- ��� ��������� ���������� ��� ������ ����������-��������,
                    �� ������ ������ ��������� ���������� ������������� �������� ��������� �������, 
                    ��������� ��������� ������ ������������
      $value -- ��������, ������� ����� ��������� ��������� ����������
      $append -- false/true - �������� ������ �������� ���������� / ������� ����� � ������� ��� ������������ �����

  * AssignRef($handler,&$value) -- ����������� ��������� ��������� ������ �� ������
      ��������� ���������� '*' ������������ ��� �������� ������ �������� �� ��������
      $handler -- ��� ��������� ����������
      &$value -- ������ �� ������
      [2004-05-26]: ������ ��� �� ����� ���� ��������
      
  * GetAssigned($handler) -- ���������� ������ �� ��������� ��������� ����������
      $handler -- ��� ��������� ����������

  * GetAction( $func_name, $string, $PARAMS=array() ) -- ���������� ����������� ���������,
              ���� �� �����, ������������� �������
              - $string - ������������ ��������, ����� ���� ������� �� ������ ����������
              - $PARAMS - �������������� ��� ����������
              ���� ������ ���, ��� $string ������� � $PARAMS � ������ ['__string']
              ������ "��� ����" �� ����� ������
              �������-���� ������ ��������� ������ �� ������ ���������� ������ � ��� ����������
  
  * Trace( $msg = "" ) -- ���������� ��������� � ���
  
  * Error($str) -- ������� � ���������� �� ������

  * UTime() -- ���������� ����� � ��������������. �� ����� ���� ��������.

  * SetRoot( $root_path ) -- ������������� ��������� ���������� � �������� �������� ��� ��������,
            ���� �� �������� ���� �� ������������, �� ������������� �������
  
  * StripEmpty( &$text ) -- �������� �������������� ���������� ��� ������ �� ������
  
  * Free( $to_reset="" ) -- �������� ��������� ����������
    - $to_reset -- ��� ��������:
                  ���� ������ ���, �������� ��� ���������
                  ���� ������, �� �������� ��������� ����������
                  ���� �� �������, �� �������� ��� ����������
  
  * GetValue($handler) -- ���������� ��������� ��������� ����������
                        ���� $handler � ������� "[���]->[����]", 
                        �� ���������� [����] ���������� ��� ������, � ������������ ��� ���� [����]
      $handler -- ��� ��������� ����������
  ------------------

���������:
- ������������� ��������� ��������
- ����� ������������
- ����� �������
  
��������:
- �������� ��������
- ������� ��������� ������
- ������� ���������� � Parse()
  
ToDo:
- ����������� � ���������� ���������� � $this->VALUES: 
  � {} � ��� ���                    �� - ����� ��� ���
- ����������� ������� ����� explode OK
  - ����������� ����������          OK
  - �������� ��������� ��������     OK
- ����������� ������������� ���������   OK
- ������� ������� ���������� ������� �� ForExample() (���� ��� ��� �� �������� �����)
- ���������� �������� ��������� �� ������: Trace($string) OK
  
- ����������� ����� ������� (���������� ������) OK
- ����������� ����� ����������� (��������� ��������� ������)  OK
- ����������� ��������� ����� ����������� - ���� �� �����   OK
  
- �������� �������� ������������� (�� �����, � ���� ��� 8(( )
  
=============================================================== v.3 (Zharik)
*/
  
class OSFastTemplate {
  
  var $SIGNATURES = array();  //sintax sugnatures
  var $SIGNS = array();       //$SIGNATURES after addslashes()
  
  var $ACTIONS = array();
  var $PRE_FILTERS = array(); //before caching
  var $POST_FILTERS = array();  //after parsing
  
  var $TEMPLATES = array();
  var $VALUES = array();
  
  var $ERRORS = array();
  var $DIRS = array();  //keys: root, templates, cache, actions
  var $CFG = array();   //keys: strict, mark, keep_empty, skip_cache
  
  var $SCTR = array("/"=>"^",":"=>"..");  //for spec chars translation in file names durnig caching
  var $preg_name_pattern = "";  //for pregs, when looking for names in templates
  var $preg_call_pattern = "";  //for pregs, when looking for action calls and formatters
  var $TO_QUOTE = array(
    '$'=>'\$',
    '!'=>'\!',
    '*'=>'\*',
  );  //for regexp building
  
  var $log; //string, contains all messages
  
  /*** constructor ***/
  function OSFastTemplate ( $root_path , $cache_path, $SIGNATURES = array() ) {
    //set default
    $this->SetRoot($root_path);
    $this->DIRS["cache"] = $cache_path;
    //spec chars settings
    $this->preg_name_pattern = "[A-Za-z0-9\s\._'\*\-\>\/\#]+";
    $this->preg_call_pattern = "[A-Za-z0-9�-��-߸�\s\.,\-_\!\?'\#\>\*\/\s\=\:\"();]+";
    //signatures
    $this->SIGNATURES = array(
      '{'=>'{{',
      '}'=>'}}',
      'tpl'=>'TPL',
      'var'=>'$',
      'call'=>'!',
      'call_block'=>'!!',
      '_var'=>'%-var-%',
      '_call'=>'%-call-%',
      '_call_block'=>'%-call_block-%',
      '_par'=>'%-par-%',
    );
    $this->SIGNATURES = array_merge($this->SIGNATURES,$SIGNATURES);
    foreach($this->SIGNATURES as $k=>$v){
      $this->SIGNS[$k] = strtr($v,$this->TO_QUOTE);
    } 
  }
  
  /*** Loads template into memory, cache it may be ***/
  function _Load( $tpl_file, $full_path = false ){
    
    //only string can be accepted
    if( gettype($tpl_file) != "string" ) {
      $this->Error("argument for load() must be a string.");
      return 1;
    }
    
    //don't load the same template twice
    if(isset($this->TEMPLATES[$tpl_file])) return;
    
    //check for cached file
    if( !$full_path )
    {
      $arr = explode(":",$tpl_file);
      $_file_cached = $this->DIRS["cache"].strtr($tpl_file,$this->SCTR);//$this->DIRS["cache"].$arr[0];
//      $_file_original = $this->DIRS["templates"].$arr[0];
      $_file_original = $this->_get_tpl_filename($arr[0]);
      
      if( !$this->CFG["skip_cache"] && @file_exists($_file_cached) && (@filemtime($_file_cached) >= @filemtime($_file_original)) ){
        $this->TEMPLATES[$tpl_file] = implode("",file($_file_cached));
        return;
      }else $tpl_file = $arr[0];
    }else $_file_original = $tpl_file;
    
    //echo '<h1>'.$tpl_file.'</h1>';


    //load template
    if( !file_exists($_file_original) ){
      $this->Error("can't read template file <b>".$_file_original."</b>.");
      return 1;
    }else{
      
      //load not-parsed template, it will be parsed some strings later
      $this->TEMPLATES[$tpl_file] = implode("",file($_file_original)); //load template
      
      //��������� ���������� Manifesto
      //������ ������ � _Prepare(), ������ ���� ��� ����, ��� �� �������� ������ TEMPLATE
      $text = $this->TEMPLATES[$tpl_file];
      //��������� ��������
      $text = preg_replace("/".$this->SIGNS["{"]."\@/", $this->SIGNATURES["{"].$this->SIGNATURES["call"].'include tpl=', $text);
      //������ css
      $text = preg_replace("/".$this->SIGNS["{"]."\&css:/", $this->SIGNATURES["{"].$this->SIGNATURES["call"].'css file=', $text);
      //������ css
      $text = preg_replace("/".$this->SIGNS["{"]."\&js:/", $this->SIGNATURES["{"].$this->SIGNATURES["call"].'js file=', $text);
      //���-�������
      $text = preg_replace("/".$this->SIGNS["{"]."TEMPLATE:/i", $this->SIGNATURES["{"].$this->SIGNATURES["tpl"].':', $text);
      $text = preg_replace("/".$this->SIGNS["{"]."\/TEMPLATE:/i", $this->SIGNATURES["{"].'/'.$this->SIGNATURES["tpl"].':', $text);
      //����� ����-������� - ���������� � ����� ��������
//      $text = preg_replace("/".$this->SIGNS["{"]."(".$this->preg_name_pattern."):(".$this->preg_name_pattern.")".$this->SIGNS["}"]."/", $this->SIGNATURES["{"].'\1->\2'.$this->SIGNATURES["}"], $text);
  //    die($text);
      $this->TEMPLATES[$tpl_file] = $text;
      
      //subtemplates and vars
      $STACK[] = $tpl_file;
      while(count($STACK)){
        $handler = array_pop($STACK);       
//        echo $handler."<br>";
        
        //extract subtemplates          
        preg_match_all( "/".$this->SIGNS["{"].$this->SIGNS["tpl"].":(".$this->preg_name_pattern.")".$this->SIGNS["}"]."(.*)".$this->SIGNS["{"]."\/".$this->SIGNS["tpl"].":\\1".$this->SIGNS["}"]."/s", $this->TEMPLATES[$handler], $vars );
        for($i=0;$i<count($vars[0]);$i++){
          $new_handler = $handler.":".$vars[1][$i];
          $this->TEMPLATES[$new_handler] = $vars[2][$i];
          $STACK[] = $new_handler;
        }
        
        //preparse template
        $this->_Prepare( $this->TEMPLATES[$handler] );
        
        //save the template for cache
        if( !$full_path ){
          $fp = fopen( $this->DIRS["cache"].strtr($handler,$this->SCTR) ,"w");
          fputs($fp,$this->TEMPLATES[$handler]);
          fclose($fp);
        }
      }
    }
    
    return 0;
  }
  
  function _get_tpl_filename( $tpl_file ){
    return $this->DIRS["templates"].$tpl_file;
  }
  
  function _Prepare( &$text ){
    
    //replace TPL entries with VAR entries
    $text = preg_replace( "/".$this->SIGNS["{"].$this->SIGNS["tpl"].":(".$this->preg_name_pattern.")".$this->SIGNS["}"]."(?:.*)".$this->SIGNS["{"]."\/".$this->SIGNS["tpl"].":\\1".$this->SIGNS["}"]."/s", $this->SIGNS["{"].$this->SIGNS["var"]."$1".$this->SIGNS["}"], $text );
    
    //preparse VAR entries
    //$text = preg_replace( "/".$this->SIGNS["{"].$this->SIGNS["var"]."(".$this->preg_name_pattern.")".$this->SIGNS["}"]."/s", $this->SIGNS["_var"]."$1".$this->SIGNS["_var"], $text );
    //preparse VAR entries with formatters
    $text = preg_replace( "/".$this->SIGNS["{"].$this->SIGNS["var"]."?(".$this->preg_name_pattern."(?:\|".$this->preg_call_pattern.")*)".$this->SIGNS["}"]."/s", $this->SIGNS["_var"]."$1".$this->SIGNS["_var"], $text );
    
    //prepare formatters calls
    $ARR = explode( $this->SIGNS["_var"], $text);
    /*
    for($i=1;$i<count($ARR);$i+=2)
      $ARR[$i] = preg_replace( "/\s|\=/", $this->SIGNS["_par"], $ARR[$i]);
    $text = implode( $this->SIGNS["_var"], $ARR );
    */
    
    //preparse CALL BLOCK entries
    $text = preg_replace( "/".$this->SIGNS["{"].$this->SIGNS["call_block"]."(".$this->preg_name_pattern.")([^\}]*)".$this->SIGNS["}"]."(.*?)".$this->SIGNS["{"]."\/".$this->SIGNS["call_block"]."\\1".$this->SIGNS["}"]."/s", $this->SIGNS["_call_block"]."$1$2".$this->SIGNS["_call_block"]."$3".$this->SIGNS["_call_block"], $text );
    /*
    $ARR = explode( $this->SIGNS["_call_block"], $text);
    for($i=1;$i<count($ARR);$i+=3)
      $ARR[$i] = preg_replace( "/\s|\=/", $this->SIGNS["_par"], $ARR[$i]);
    $text = implode( $this->SIGNS["_call_block"], $ARR );
    */
    
    //preparse CALL entries
    $text = preg_replace( "/".$this->SIGNS["{"].$this->SIGNS["call"]."(".$this->preg_call_pattern.")".$this->SIGNS["}"]."/s", $this->SIGNS["_call"]."$1".$this->SIGNS["_call"], $text );
    /*
    $ARR = explode( $this->SIGNS["_call"], $text);
    for($i=1;$i<count($ARR);$i+=2)
      $ARR[$i] = preg_replace( "/\s|\=/", $this->SIGNS["_par"], $ARR[$i]);
    $text = implode( $this->SIGNS["_call"], $ARR );
    */
    
    //pre filters
    for($i=0;$i<count($this->PRE_FILTERS);$i++)
      $text = $this->Action( $this->PRE_FILTERS[$i], $text );
  }
  
  /*** Assigns values to tag handlers ***/
  function Assign( $assign_map, $value="", $append=false ){
    
    if( gettype($assign_map) != "array" ){
      //only one pair
      if($append) $this->VALUES[ $assign_map ] .= $value; 
      else $this->VALUES[ $assign_map ] = $value; 
    }else{
      //assign values to tag name
      reset( $assign_map );
      while( list($handler,$value) = each($assign_map) ) $this->VALUES[ $handler ] = $value;
    }
  }
  
  /*** Assigns values to tag handlers by reference ***/
  function AssignRef($handler,&$value){
    $this->VALUES[ $handler ] =& $value;
  }
  
  /*** return value, assigned to the handler ***/
  function &GetAssigned( $handler="" ){
    return $this->VALUES[$handler];
  }

  /**
   * rocket-TE wrapper functinons
   * for reducing missprints
   * nop @ 10:46 22.04.2007
   */
  function set($assign_map, $value="", $append=false)
  {
      $this->assign($assign_map, $value="", $append=false);
  }
  
  function setRef($handler,&$value)
  {
      $this->assign($handler,&$value);
  }
  
  function get($handler="")
  {
    return $this->getAssigned($handler);
  }
  
  /*** Actual parcing ***/
  function &Parse ( $tpl, $store_to="", $append=false, $full_path=false ){
    
    if( !$full_path && $tpl[0]=="." ){
      $tpl = substr($tpl,1);
      $append = true;
    }
    $this->_cur_tpl = $tpl;
    
    //check $tpl
    if( !isset($this->TEMPLATES[$tpl]) ) $this->_Load( $tpl, $full_path );
    
    //�������������� ��������
    if(!isset($this->TEMPLATES[$tpl]))
      $this->Error("template isn't loaded: ".$tpl."</b>.");
    
    //preapare marks
    switch( $this->CFG["mark"] ){
      case 'comments':
        $mark = "\n<!--keep TEMPLATE: ".$tpl." -->\n";
        $_mark = "\n<!--keep / TEMPLATE: ".$tpl." -->\n";
      break;
      case 'bold':
        $mark = "\n<b>TEMPLATE: ".$tpl."</b>\n";
        $_mark = "\n<b> / TEMPLATE: ".$tpl."</b>\n";
      break;
      default:
        $mark = $_mark = "";
      break;
    }
    
    return $mark.$this->_Parse( $this->TEMPLATES[$tpl], $store_to, $append ).$_mark;
  }
  
  function ParseFromString( $text, $store_to="", $append=false ){
    $this->_Prepare( $text );
    $this->_Parse( $text, $store_to, $append );
  }
  
  function &_Parse( $text, $store_to="", $append=false ){
    
    //init variables from template
    preg_match_all("/".$this->SIGNS["{"].$this->SIGNS["var"]."?(".$this->preg_name_pattern."=".$this->preg_name_pattern.")".$this->SIGNS["}"]."/",$text,$vars);
    for($i=0;$i<count($vars[0]);$i++){
      $tt = explode("=",$vars[1][$i]);
      $this->VALUES[ $tt[0] ] = $tt[1];
    }
    $text = preg_replace("/".$this->SIGNS["{"].$this->SIGNS["var"]."?(".$this->preg_name_pattern."=".$this->preg_name_pattern.")".$this->SIGNS["}"]."/","",$text);
    
    //temporary params hash
    $PARAMS = array();
    
    //parse VAR entries
    $ARR = explode( $this->SIGNS["_var"], $text );
    $text = $ARR[0];
    //subst vars
    for($i=1;$i<count($ARR);$i+=2){
      //many formatters may be
      $FRMT = explode("|",$ARR[$i]);
      if(count($FRMT)>1){
        //get value
        $val = $this->GetValue($FRMT[0]);
        //for all formatters
        for($l=1;$l<count($FRMT);$l++){
          //get action and params
          $PARAMS = array();
          $action = $this->_name_params( $FRMT[$l], $PARAMS );
          //do formatter
          $val = $this->Action( $action, $val, $PARAMS );
        }
        $text .= $val . $ARR[ $i+1 ];
      }else 
      //no formatters
      $text .= $this->GetValue( $ARR[$i] ) . $ARR[ $i+1 ];
    }
    
    //parse CALL BLOCK entries
    $ARR = explode( $this->SIGNS["_call_block"], $text );
    $text = $ARR[0];
    for($i=1;$i<count($ARR);$i+=3){ 
      //get action and params
      $PARAMS = array();
      $action = $this->_name_params( $ARR[$i], $PARAMS );
      //do action
      $text .= $this->Action( $action, $ARR[$i+1], $PARAMS ) . $ARR[ $i+2 ];
    }
    
    //parse CALL entries
    $ARR = explode( $this->SIGNS["_call"], $text );
    $text = $ARR[0];
    for($i=1;$i<count($ARR);$i+=2){ 
      //get action and params
      $PARAMS = array();
      $action = $this->_name_params( $ARR[$i], $PARAMS );
      //do action
      $text .= $this->Action( $action, $PARAMS ) . $ARR[ $i+1 ];
    }
    
    //post filters
    for($i=0;$i<count($this->POST_FILTERS);$i++){
      //filter
      $text = $this->Action( $this->POST_FILTERS[$i], $text );
    }
    
    $this->StripEmpty( $text );
    
    //resolve $append condition
    if( $store_to!="" )
      $this->VALUES[$store_to] = ( $append ? $this->VALUES[$store_to] : '' ).$text;
    
    //clear variables, taken from the template
    for($i=0;$i<count($vars[0]);$i++){
      $tt = explode("=",$vars[1][$i]);
      $this->VALUES[ $tt[0] ] = "";
    }
    
    return $text;
  }
  
  /*
  function _name_params( $string, &$PARAMS){
    $A = explode( $this->SIGNS["_par"], $string );
    //turn params into hash
    $PARAMS = array();
    for($j=1;$j<count($A);$j+=2) $PARAMS[ $A[$j] ] = $A[$j+1];
    //return name
    return $A[0];
  }
  */
  
  //from  the "rocket engine", by kuso@npj
  function _name_params( $content, &$params){
//    $params = array();
    $params["_plain"] = $content;
    // 1. get name by explosion
    $a = explode(" ", $content);
    $params["_name"] = strtolower($a[0]);
    if (sizeof($a) == 1) return $params["_name"];
    // 2. link`em back
    $a = array_slice( $a, 1 );
    $_content = " ".implode(" ", $a);
    // 3. get matches      1     2       3 45       6    7      8  9
    $c = preg_match_all( "/(^|\s)([^= ]+)(=((\"|')?)(.*?)(\\4))?($|(?=\s))/i",
                         $_content, $matches, PREG_SET_ORDER  );
    // 4. sort out
    $named = array();
    foreach( $matches as $match )
    {
      if ($match[3]) // named parameter
        $named[ $match[2] ] = $match[6];
      else // unnamed parameter
        $params[] = $match[2];
    }
    foreach($named as $k=>$v) $params[$k] = $v;
    return $params["_name"];
  }
  
  /*** work with functions (plugins) ***/
  //Action() �������� ��������� � OSFastTemplatesWrapper
  //������� ����� � ���������
  /*function Action( $func_name, &$string, $PARAMS=array() ){
    //check function
    if( !isset($this->ACTIONS[$func_name]) )
      $this->Error("Action <b>".$func_name."</b> is not defined.");
    if( !function_exists($this->ACTIONS[$func_name]) )
      $this->Error("Action <b>".$this->ACTIONS[$func_name]."</b> is not supported.");
    //prepare params
    if( is_array($string) ) $PARAMS =& $string;
    else $PARAMS['__string'] =& $string;
    //action!
    $action = $this->ACTIONS[$func_name];
    return $action( $this, $PARAMS );
  }*/
  
  function CheckAction( $func_name ){
    return isset( $this->ACTIONS[$func_name] );
  }
  
  /*** saves error message ***/
  function Trace( $msg = "" ){
    $this->log .= $msg."<br>\n";
  }
  
  function Error($str){
    die("<b>OSFastTemplate error:</b> ".$str);
  }
  
  /*** high pressision time for benchmarking ***/
  function UTime(){
    
    $time = explode( " ", microtime());
    $usec = (double)$time[0];
    $sec = (double)$time[1];
    
    return $sec + $usec;
  }
  
  /*** MISC ***/
  function SetRoot( $root_path ) {
    if($root_path!="" && !is_dir($root_path) ) $this->Error("Specified path <b>$root_path</b> is not a directory.");
    else $this->DIRS["templates"] = $root_path;
  }
  
  function StripEmpty( &$text ){
    if( !$this->keep_empty )
      $text = preg_replace( "/".$this->SIGNS["{"].$this->preg_name_pattern.$this->SIGNS["}"]."/", "", $text );
  }
  
  function Free( $to_reset="" ){
    if(is_array($to_reset))
      for($i=0;$i<count($to_reset);$i++) unset($this->VALUES[$to_reset[$i]]);
    else{
      if($to_reset!="") unset($this->VALUES[$to_reset]);
      else $this->VALUES = array();
    }
  }
  
  function GetValue($handler){
    if( $handler[0]=='*' ){
      //quick object
      $tt = substr($handler,1);
      return is_array($this->VALUES['*']) ? $this->VALUES['*'][$tt] : $this->VALUES['*']->$tt;
      
    }else if( $handler[0]=='#' ){
      //object
      $_A = explode('->', $handler );
      $t1 = substr($_A[0],1);
      $t2 = $_A[1];
      return is_array($this->VALUES[$t1]) ? $this->VALUES[$t1][$t2] : $this->VALUES[$t1]->$t2 ;
    }else
      //plain
      return $this->VALUES[ $handler ];
  }
}

?>
