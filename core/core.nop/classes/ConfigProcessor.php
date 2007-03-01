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
  function FindScript( $type, $name, $level=0, $dr=1, $ext = 'php' )
  {
    
    //��������� ������� ������
    if($type==''){
      $error = "FindScript: <b>*type* �����</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>";
      if( $this->debug )
        $this->debug->Error($error);
      else
        die($error);
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
      }
      //���� ������ ������ �� ����� ������ - ����� �������
      if($dr==0)
        break;
    }
    
    //������ �� �����
    return false;
  }
  
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
  
  //����, ��� � FindScript_(), �� ����� ���� �������� ��������� ������
  function UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ){
    include_once( $this->FindScript_($type,$name,$level,$dr,$ext) );
  }
  
  
}

?>