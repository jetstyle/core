<?php
/*
  ����������� �����, �������������� ���������������� ����������� ��������
  
  ===================
  
  //����� ������
  
  * FindScript ( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- ���� ������ �� ������� ��������.
    ����:
      $type -- ��������� �������, �������� classes, handlers, actions � ��.
      $name -- ������������� ��� ����� � �������� ������������, ��� ���������� 
      $level -- ������� �������, ������� � �������� ����� ������ ����
                ���� �� �����, ������ ������ ������ ����������
      $dr -- ����������� ������, ��������� �������� : -1,0,+1
      $ext -- ���������� �����, ������ �� �����������
      $this->DIRS -- ������ �������� ���������� ��� ������� ������ �������,
        ��� ������� ������ ����� ���� ������:
        $dir_name -- ������, ��� �������� ����������
        array( $dir_name, $TYPES ):
          $dir_name -- ������, ��� �������� ����������
          $TYPES -- ������������, ����� ���� �� ������ ����
    �����:
      ������ ��� �������, ������� ����� �������� � include()
      false, ���� ������ �� ������
  
  * FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- �� ��, ��� � FindScript, 
              �� � ������ �� ����������� ����� ������������ � �������.
  
  * UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- �� ��, ��� � FindScript_, 
              �� ������������� �������� ������
  
*/
  
class ConfigProcessor {
  
  var $DIRS = array(); //���������� � �������� ����������� ��� ������� ������
  
  //���� ������ �� ������� ��������.
  function FindScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
  {
    //��������� ������� ������
    if($type=='')
    {
      throw new Exception("FindScript: <b>*type* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
    }

    //���������� ��������� ������� ������
    $n = count($this->DIRS);
    if($level===false) $level = $n - 1;
    $i = $level>=0 ? $level : $n - $level;

    //����
    for( ; $i>=0 && $i<$n; $i+=$dr )
    {
      //������ ������� ������ ���
      $dir =& $this->DIRS[$i];
      if( !( is_array($dir) && !in_array($type,$dir) ) )
      {
        $fname = (is_array($dir) ? $dir[0] : $dir).$type."/".$name.'.'.$ext;
//        echo $fname.'<br />';
        if(@file_exists($fname))
          return $fname;

        if ($withSubDirs)
        {
          if ($file = $this->recursiveFind((is_array($dir) ? $dir[0] : $dir).$type."/", $name . "." . $ext))
		return $file;
	}
      }
      //���� ������ ������ �� ����� ������ - ����� �������
      if($dr==0)
        break;
    }
    
    //������ �� �����
    return false;
  }

  private function recursiveFind($dir, $name)
  {
    if ($handle = @opendir($dir)) 
    {
      while (false !== ($file = readdir($handle))) 
      {
        if ($file == "." || $file == "..")
          continue ;  
        if ($file == $name)
          return $dir . "/" . $file;
        if (is_dir($dir . $file))
        {
          if ($res = $this->recursiveFind($dir . $file, $name))
          {
            closedir($handle);
            return $res;
          }
        }
      }
      closedir($handle);
    }
    return false;
  }
  
  //newschool
  //����, ��� � FindScript(), �� � ������ �� ����������� ����� ������������ � �������
  function FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php', $withSubDirs = false ){
    try
    {
      if (!$fname = $this->FindScript($type,$name,$level,$dr,$ext,$withSubDirs))
        throw new FileNotFoundException("FindScript: <b>not found</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
      else
        return $fname;
    }
    catch (FileNotFoundException $e)
    {
      $exceptionHandler = ExceptionHandler::getInstance();
      $exceptionHandler->process($e);
    }
  }

/* 
  //oldschool
  //����, ��� � FindScript(), �� � ������ �� ����������� ����� ������������ � �������
  function FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ){
    $fname = $this->FindScript($type,$name,$level,$dr,$ext);
    if($fname)
      return $fname;
    else
    {
      $error = "FindScript: <b>not found</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>";
      if( $this->debug )
        $this->debug->Error($error);
      else
        die($error);
    }
  }
*/  
  function FindDir($name)
  {
    //���������� ��������� ������� ������
    $n = count($this->DIRS);
    $level = $n - 1;
    $i = $level>=0 ? $level : $n - $level;

    //����
    for( ; $i>=0 && $i<$n; $i-=1 )
    {
      //������ ������� ������ ���
      $dir =& $this->DIRS[$i];
	  if (is_dir($dir . $name))
		  return true;
    }
    
    //������ �� �����
    return false;
  }

  //����, ��� � FindScript_(), �� ����� ���� �������� ��������� ������
  function UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php', $withSubDirs = false, $hideExc = false ){
    $method = ($hideExc) ? "FindScript" : "FindScript_";
    if ($path = $this->$method($type,$name,$level,$dr,$ext,$withSubDirs))
      $this->_useScript( $path );
  }
  
  // ������ ������ � ��������� ����
  function _useScript($source)
  {
    include_once( $source );
  }
}
?>