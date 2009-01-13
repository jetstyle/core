<?
  
/*
  Upload -- �������������� ������ � �������
  
  ---------
  
  * Upload ( &$rh, $dir="", $table_name='' ) -- �����������
    - $rh -- ������ �� $rh
    - $dir -- ����������, ������� � ������� ������ �����
    - $table_name -- ��� �������, � ������� ������� ������ � �����
  
  * _Current( $file_name, $ext ) -- ��������� ������ ����� ��� ���������� ����� � ����������
    - $file_name -- ��� ����� ��� ���������� �� ������ �������� ����������
    - $ext -- ���������� �����
  
  * CheckExt($ext,$type) -- ���� ��� ���������� ���� �� �������� content-type, �� ���������� ��������� content-type
    - $ext -- ����������
    - $type -- content-type ��� ���������� ����������
  
  * IsAllowed($ext) -- ���������, ��������� �� �������� � ������ ����������� ����� ������ ��� ����� ������
  
  * UploadFile($input_name,$file_name,$is_full_name=false) -- ��������� �������� �����
      ���������� ������ �������� �����
    - $input_name -- ��� ���� ����� ���� file, ����� ������� ��������� ����
    - $file_name -- ����� ��� �����, ��� ����������
    - $is_full_name - ���� true, �� ��������� ���� � �������� ��� ��������� ������ $file_name
  
  * GetFile($file_name,$is_full_name=false) -- ���������� ��������������� ������ �����
    - $is_full_name - ���� true, �� ���� ���� � �������� � ������ $file_name
  
  * DelFile($file_name,$is_full_name=false) -- �� ��, ��� � GetFile, ������ ��������� ���� ���������
    - $file_name -- ��� ����� ��� ���������� 
    - $is_full_name - ���� true, �� ������� ���� � �������� � ������ $file_name
  
  * GZip($file_name,$type="") -- ������������ ��������� ���� � �����
    - $file_name -- ��� ����� ��� ���������� 
    - $type -- ���� ������, �� ������ ���� ������ ����� ����
  
  * _GZip($file_name) -- ������������ ��������� ���� � �����
      ��� ���� ����������� �� ����� ��������� � ��������� .gz
    - $file_name -- ���������� ��� �����
  
  ������ �����:
  $current->name_full -- ���������� ��� �����
  $current->name_short -- ��� ����� � �����������, ��� ������ ����������
  $current->ext -- ���������� �����
  $current->format -- ������ �����, ������������, �������� "MsWord"
  $current->_format -- ������ �����, ���� content-type, �������� "application/msword"
  $current->size -- ������ ����� � ����������
  $current->link -- ��� ����� � ����������� � �������� ����������
  
=============================================================== v.2 (Zharik)

*/
class Upload {

  var $rh;  
  var $dir;
  var $current = false; //��������� �����������/��������� ����
  var $table_name; //��� �������, � ������� ������� ������ � �����
  var $chmod = 0744; //����� ����� ���������� �� ����������� ����
  
  var $TYPES = array(); // ext => [type,word]
  var $ALLOW = array(); // ����� ������ ����������
  var $DENY = array(); // ������ ������ ����������
  var $DIRS_SWAPPED = array(); //��� DirSwap(),  DirUnSwap();
  
  function Upload(&$rh,$dir="",$table_name=''){
    $this->rh =& $rh;
    $this->dir = $dir;//with trailing '/'
    $this->table_name = $table_name ? $table_name : $rh->project_name.'_upload';
    $this->chmod = 0744;
    //������ ���� ������
    $rs = $rh->db->execute("SELECT * FROM ".$this->table_name);
    while(!$rs->EOF){
      $this->TYPES[ $rs->fields['ext'] ] = array($rs->fields['type'],$rs->fields['title']);
      $rs->MoveNext();
    }
  }
  
  function _Current($file_name,$ext){
    $file_name_ext = $file_name.".".$ext;
    $file_name_full = $this->dir.$file_name_ext;
    $this->current->name_full = $file_name_full;
    $this->current->name_short = $file_name_ext;
    $this->current->ext = strtolower($ext);
    $this->current->format = $this->TYPES[$ext][1];
    $this->current->_format = $this->TYPES[$ext][0];
    $this->current->size = floor(100.0*@filesize($file_name_full)/1024)/100;
    $this->current->link = $this->dir.$this->current->name_short;
  }
  
  function CheckExt($ext,$type){
    if(!isset($this->TYPES[$ext])){
      $this->TYPES[$ext] = array( $type, $ext );
      $this->rh->db->execute("INSERT INTO ".$this->table_name."(ext,type,title) VALUES('$ext','$type','$ext')");
    }
  }
  
  function IsAllowed($ext){
    if( count($this->ALLOW) && !in_array($ext,$this->ALLOW) 
        || count($this->DENY) && in_array($ext,$this->DENY) )
      return false;
    return true;
  }
  
  function UploadFile( $input_name, $file_name, $is_full_name=false ){
    $uploaded_file = $_FILES[ $input_name ]['tmp_name'];
    if(is_uploaded_file($uploaded_file)){
      $this->current = false;
      //���������� ������
      $type = $_FILES[ $input_name ]['type'];
      $ext = explode(".",$_FILES[ $input_name ]['name']);
      $ext = $ext[ count($ext)-1 ];
      //�������� �� ������
      if( !$this->IsAllowed($ext) ) return false;
      //������
      $this->CheckExt($ext,$type);
      $this->DelFile($file_name);         //if($del_prev) ...
      $file_name_ext = $file_name.".".$ext;
      $file_name_full = ( $is_full_name )? $file_name : $this->dir.$file_name_ext;
      move_uploaded_file($uploaded_file,$file_name_full);
      chmod($file_name_full,$this->chmod);
      $this->_Current($file_name,$ext);
      return $this->current;
    }
  }
  
  function GetFile( $file_name, $is_full_name=false ){
    $this->current = false;
    //����� ���������� �� ������� �����?
    if( $is_full_name && @file_exists($file_name) ){
      $path_info = pathinfo($file_name);
      $ext = $path_info['extension'];
      $file_name = basename($file_name,'.'.$ext);
    }
    //������� �� ������ ��� - ���� ����������
    if($ext==''){
      $A = array_keys($this->TYPES);
      foreach($A as $ext){
      	//$ext = strtolower($ext);
        if(@file_exists($this->dir.$file_name.'.'.$ext))
        {
          break;
        }
        else $ext = '';
      }
    }
    if($ext!=''){
      $this->_Current($file_name,$ext);
      return $this->current;
    }
    return false;
  }
  
  function DelFile( $file_name,  $is_full_name=false  ){
    if( $is_full_name ) @unlink($file_name);
    else{
      $A = array_keys($this->TYPES);
      foreach($A as $ext)
      {
        $file_name_full = $this->dir.$file_name.".".$ext;
        if(@file_exists($file_name_full)) unlink($file_name_full);
      }
    }
  }
  
  //GZip functions
  /*
    ���� ������ �� �����
    ��������, �� ����� ����� ����������
  */
/*  function GZip($file_name,$type=""){
    //find file/files
    if($ext==""){
      for($i=0;$i<count($this->TYPES);$i++){
       $file_name_full = $this->dir.$file_name.".".$this->TYPES[$i][1];
       if(file_exists($file_name_full)) $this->_GZip($file_name_full);
      }
    }else{
     $file_name_full = $this->dir.$file_name.".".$ext;
     if(file_exists($file_name_full)) $this->_GZip($file_name_full);
    }
  }
  
  function _GZip($file_name){
    if(file_exists($file_name)){
      $zp = gzopen($file_name.".gz","w9");
      $fp = fopen($file_name,"r");
      gzwrite($zp,fread($fp,filesize($file_name)));
      fclose($fp);
      gzclose($zp);
    }
  }*/
  
  function DirSwap($dir){
    $this->DIRS_SWAPPED[] = $this->dir;
    $this->dir = $dir;
  }
  
  function DirUnSwap($all=false){
    if( count($this->DIRS_SWAPPED) )
      if( $all ){
        $this->dir = $this->DIRS_SWAPPED[0];
        $this->DIRS_SWAPPED = array();
      }else $this->dir = array_pop($this->DIRS_SWAPPED);
  }
}

?>
