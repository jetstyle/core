<?
  
/*
  Upload -- типизированная работа с файлами
  
  ---------
  
  * Upload ( &$rh, $dir="", $table_name='' ) -- конструктор
    - $rh -- ссылка на $rh
    - $dir -- директория, начиная с которой ищутся файлы
    - $table_name -- имя таблицы, в которой копятся данные о типах
  
  * _Current( $file_name, $ext ) -- заполняет объект файла для указанного файла и расширения
    - $file_name -- имя файла без расширения от начала корневой директории
    - $ext -- расширение файла
  
  * CheckExt($ext,$type) -- если для указанного типа не известен content-type, то дописывает указанный content-type
    - $ext -- расширение
    - $type -- content-type для указанного расширения
  
  * IsAllowed($ext) -- проверяет, разрешены ли операции с данным расширением через чёрные или белые списки
  
  * UploadFile($input_name,$file_name,$is_full_name=false) -- обработак загрузки файла
      возвращает объект текущего файла
    - $input_name -- имя поля формы типа file, через которое загружали файл
    - $file_name -- новое имя фалйа, без расширения
    - $is_full_name - если true, то сохраняет файл в точности под указанным именем $file_name
  
  * GetFile($file_name,$is_full_name=false) -- возвращает соответствующий объект файла
    - $is_full_name - если true, то ищет файл в точности с именем $file_name
  
  * DelFile($file_name,$is_full_name=false) -- то же, что и GetFile, только найденный файл удаляется
    - $file_name -- имя файла без расширения 
    - $is_full_name - если true, то удаляет файл в точности с именем $file_name
  
  * GZip($file_name,$type="") -- запаковывает указанный файл в архив
    - $file_name -- имя файла без расширения 
    - $type -- если указан, то ищется файл только этого типа
  
  * _GZip($file_name) -- запаковывает указанный файл в архив
      имя фала формируется из имени оригинала с суффиксом .gz
    - $file_name -- абсолютное имя файла
  
  объект файла:
  $current->name_full -- абсолютное имя файла
  $current->name_short -- имя файла с расширением, без всяких директорий
  $current->ext -- расширение файла
  $current->format -- формат файла, аббривеатура, например "MsWord"
  $current->_format -- формат файла, типа content-type, например "application/msword"
  $current->size -- размер файла в килобайтах
  $current->link -- имя файла с расширением и корневой директорий
  
=============================================================== v.2 (Zharik)

*/
class Upload {

  var $rh;  
  var $dir;
  var $current = false; //последний загруженный/выбранный файл
  var $table_name; //имя таблицы, в которой хранить данные о типах
  var $chmod = 0744; //какие права выставлять на загруженный файл
  
  var $TYPES = array(); // ext => [type,word]
  var $ALLOW = array(); // белый список расширений
  var $DENY = array(); // чёрный список расширений
  var $DIRS_SWAPPED = array(); //для DirSwap(),  DirUnSwap();
  
  function Upload(&$rh,$dir="",$table_name=''){
    $this->rh =& $rh;
    $this->dir = $dir;//with trailing '/'
    $this->table_name = $table_name ? $table_name : $rh->project_name.'_upload';
    $this->chmod = 0744;
    //читаем базу знаний
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
      //клиентские данные
      $type = $_FILES[ $input_name ]['type'];
      $ext = explode(".",$_FILES[ $input_name ]['name']);
      $ext = $ext[ count($ext)-1 ];
      //проверка на допуск
      if( !$this->IsAllowed($ext) ) return false;
      //грузим
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
    //взять расширение из полного имени?
    if( $is_full_name && @file_exists($file_name) ){
      $path_info = pathinfo($file_name);
      $ext = $path_info['extension'];
      $file_name = basename($file_name,'.'.$ext);
    }
    //указано не полное имя - ищем расширение
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
    пока никому не нужны
    возможно, их нужно нафиг переписать
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
