<?php
/*
  Абстрактный класс, обеспечивающий функциональность обнаружения скриптов
  
  ===================
  
  //поиск файлов
  
  * FindScript ( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- Ищет скрипт по уровням проектов.
    ВХОД:
      $type -- псевдотип скрипта, например classes, handlers, actions и пр.
      $name -- относительное имя файла в каталоге псевдокласса, без расширения 
      $level -- уровень проекта, начиная с которого нужно искать файл
                если не задан, берётся равный самому последнему
      $dr -- направление поиска, возможные значения : -1,0,+1
      $ext -- расширение файла, обычно не указывается
      $this->DIRS -- массив корневых директорий для каждого уровня проекта,
        для каждого уровня может быть задано:
        $dir_name -- строка, имя корневой директории
        array( $dir_name, $TYPES ):
          $dir_name -- строка, имя корневой директории
          $TYPES -- перечисление, какие типы на уровне есть
    ВЫХОД:
      полное имя скрипта, которое можно вставить в include()
      false, если скрипт не найден
  
  * FindScript_( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript, 
              но в случае не обнаружения файла вываливается с ошибкой.
  
  * UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php' ) -- То же, что и FindScript_, 
              но дополнительно инклюдит скрипт
  
*/
  
class ConfigProcessor {
  
  var $DIRS = array(); //информация о корневых директориях для каждого уровня
  
  //Ищет скрипт по уровням проектов.
  function FindScript( $type, $name, $level=0, $dr=1, $ext = 'php', $withSubDirs = false )
  {
    //проверяем входные данные
    if($type=='')
    {
      throw new Exception("FindScript: <b>*type* пусто</b>, type=<b>$type</b>, name=<b>$name</b>, level=<b>$level</b>, dr=<b>$dr</b>, ext=<b>$ext</b>");
    }

    //определяем начальный уровень поиска
    $n = count($this->DIRS);
    if($level===false) $level = $n - 1;
    $i = $level>=0 ? $level : $n - $level;

    //ищем
    for( ; $i>=0 && $i<$n; $i+=$dr )
    {
      //разбор каждого уровня тут
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
      //если искать только на одном уровне - сразу выходим
      if($dr==0)
        break;
    }
    
    //ничего не нашли
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
  //Тоже, что и FindScript(), но в случае не обнаружения файла вываливается с ошибкой
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
  //Тоже, что и FindScript(), но в случае не обнаружения файла вываливается с ошибкой
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
    //определяем начальный уровень поиска
    $n = count($this->DIRS);
    $level = $n - 1;
    $i = $level>=0 ? $level : $n - $level;

    //ищем
    for( ; $i>=0 && $i<$n; $i-=1 )
    {
      //разбор каждого уровня тут
      $dir =& $this->DIRS[$i];
	  if (is_dir($dir . $name))
		  return true;
    }
    
    //ничего не нашли
    return false;
  }

  //Тоже, что и FindScript_(), но кроме того инклюдим найденный скрипт
  function UseScript( $type, $name, $level=false, $dr=-1, $ext = 'php', $withSubDirs = false, $hideExc = false ){
    $method = ($hideExc) ? "FindScript" : "FindScript_";
    if ($path = $this->$method($type,$name,$level,$dr,$ext,$withSubDirs))
      $this->_useScript( $path );
  }
  
  // Грузит скрипт в контексте меня
  function _useScript($source)
  {
    include_once( $source );
  }
}
?>