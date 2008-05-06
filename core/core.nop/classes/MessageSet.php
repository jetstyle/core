<?php
/*
  Средство для интернационализации:
  * переключение между языками
  * блоки контекстнозависимых текстовых констант

  MessageSet( &$rh )

  ---------

  // Полезные контекстнозависимые функции

  * ConvertDate( $dt ) -- преобразователь из "2004-05-20 23:37:20" => "20 мая 2004"

  * NumberString( $count, $items_name = "items" ) -- преобразователь из "23 pages" -> "23 страницы"
      - $count     -- число единиц, для которых нужно вернуть падеж
      - $items_name -- идентификатор словоформы из msg["Numbers"]
      - возвращает только "страницы", потому что остальное может быть в другом оформлении

  ---------

  // Переключение языков

  * SwitchTo( $lang = "ru" ) -- Переключение языка "на лету"
      - $lang -- префикс мессаджсетов
  
  * Get( $key, $level=false, $dir=-1 ) -- Получить значение константы
      - $key         -- имя ключа, например "email"
      - $level, $dir -- стартовое положение в стеке мессаджсетов,

  * Load( $ms_name="", $level=false, $dir=-1 ) -- догрузить messageset, расширив стек
      - $ms_name     -- имя мессаджсета без префикса и расширения, например "form" для "ru_form.php"
      - $level, $dir -- параметры поиска файла для FindScript

  * Unload( $to_level = false ) -- выгрузить messagesets до указанного уровня
      - $to_level -- если не указан, то выгружается только верхний  
      - если =0, то получаем "чистый" мессаджсет


  ---------

  Параметры для мессаджесета в $rh:

  $rh->msg_default = "ru"; 

=============================================================== v.1 (kuso@npj)
*/

class MessageSet
{
  var $rh;        // use: $this->rh->debug->Trace (..->Error)
  var $MSGS      = array(); // стек мессаджсетов
  var $MSG_NAMES = array(); // стек имён для переключения языка
  var $lang = "default";


  function MessageSet( &$rh )
  {
    $this->rh = &$rh;

    // load default msgset always
    $this->Load();

    if ($rh->msg_default) 
    {
      $this->SwitchTo( $rh->msg_default );
      $this->Load();
    }

  }

  function SwitchTo( $lang = "ru" ) // -- Переключение языка "на лету"
  {
    $names = $this->MSG_NAMES;
    // clean up ms
    $this->UnLoad(1); 
    // change lang
    $this->lang = $lang;
    // load`em back in stack
    foreach( $names as $k=>$name )
     if ($k > 0)
      $this->Load( $name[0], $name[1], $name[2] );
  }
  
  function Get( $key, $level=false, $dir=-1 ) // -- Получить значение константы
  {
    //определяем начальный уровень поиска
    $n = count($this->MSGS);
    if($level===false) $level = $n - 1;
    $i = $level>=0 ? $level : $n - $level;

    //ищем
    for( ; $i>=0 && $i<$n; $i+=$dir )
    {
      if (isset($this->MSGS[$i][$key]))
        return $this->MSGS[$i][$key];
      //если искать только на одном уровне - сразу выходим
      if($dir==0)
        break;
    }
   
    //ничего не нашли
    return $key;
    
  }

  function Load( $ms_name="", $level=false, $dir=-1 ) // -- догрузить messageset, расширив стек
  {
    $this->MSG_NAMES[] = array($ms_name, $level, $dir);
    if ($ms_name) $ms_name = "_".$ms_name;
    $script = $this->rh->FindScript( "messagesets", $this->lang.$ms_name, $level, $dir );
    if ($script) include($script);
    else         $this->MSGS[] = array(); // load empty if not found at all
  }

  function Unload( $to_level = false ) // -- выгрузить messagesets до указанного уровня
  {
    if ($to_level === false) $to_level = sizeof($this->MSGS)-1;
    $this->MSGS = array_slice( $this->MSGS, 0, $to_level );
    $this->MSG_NAMES = array_slice( $this->MSG_NAMES, 0, $to_level );
  }


  // Полезные функции ---------------------------------------------------------------------------------
  function ConvertDate( $dt )
  {
    $months = $this->Get( "Months" );

    $dt = explode(" ",$dt);
    $d  = explode("-",$dt[0]);
    return ltrim($d[2],"0")."&nbsp;".$months[$d[1]-1]."&nbsp;".$d[0];
  }

  function NumberString( $count, $items_name = "items" )
  {
    $numbers = $this->Get( "Numbers" );
    if (!isset($numbers[$items_name])) return "!define *$items_name*!";
    $triad = $numbers[$items_name];

    $count %= 100;
    if (($count > 10) && ($count < 20)) $count = 10;
    $c = $count % 10;
    if ($c == 0) return $triad[2];
    if ($c == 1) return $triad[0];
    if ($c <= 4) return $triad[1];
    return $triad[2];
  }


// EOC{ MessageSet } 
}


?>