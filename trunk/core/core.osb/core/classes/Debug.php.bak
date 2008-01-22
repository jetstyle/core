<?php
/*
    Debug( $halt_level=0, $to_file=NULL )  -- Свой простой класс для отладки
      - $halt_level -- уровень severity, при возникновении ошибки такого или большего уровня 
                       завершает выполнение скриптов
      - $to_file    -- можно указать имя файла, тогда вывод будет не в поток вывода, а в файл

  ---------
  * Flush( $prefix="<div>Trace log:</div><ul><li>", $separator="</li><li>", $postfix="<b>done.</b></li></ul>" ) --
                             вывод лога с помощью echo(), при этом лог соответствующим образом оформляется
      - $prefix / $postfix -- вставляются в начале/конце лога
      - $separator         -- вставляется между каждыми двумя записями лога

  * Milestone( $what="" ) -- записать в лог время, прошедшее с момента предыдущего запуска Milestone() с комментарием
      - $what -- опциональный комментарий

  * Trace( $what, $flush=0 ) -- сделать запись в лог, если нужно
      - $what  -- текст записи
      - $flush -- если нужно, то после записи лог выводится в echo()

  * Trace_R( $what, $flush=0 ) -- сделать запись в лог в виде развёрнутого массива
      - $what  -- массив, который будет развёрнут
      - $flush -- если нужно, то после записи лог выводится в echo()

  * IsError( $error_level = 1 ) -- не возникало ли за время работы ошибки, true если возникало
      - $error_level -- уровень чувствительности. если были ошибки только меньшего уровня, игнорирует их

  * Error( $msg, $error_level=1 ) -- записать в лог сообщение об ошибке
      - $msg         -- сопроводительный текст ошибки
      - $error_level -- уровень severity ошибки. чем больше, тем страшней ошибка. рекомендуется (0..5)

  * Halt( $flush = 1 ) -- закончить выполнение скрипта
      - $flush -- если установлен в единицу, то ещё и выведет лог

  * ErrorHandler( $errno, $errstr, $errfile, $errline ) -- для перехвата нефатальных ошибок PHP
      - $errno -- код ошибки
      - $errstr -- сообщение об ошибке
      - $errfile -- имя файла, где ошибка
      - $errline -- номер строки, где ошибка

  // Внутренние методы
  * _getmicrotime() -- для внутреннего использования, замер времени

=============================================================== v.3 (Kuso)
*/

class Debug
{
   var $halt_level;
   var $log;
   var $_milestone;
   var $milestone;
   var $is_error;
   var $to_file;

   function Debug( $halt_level=0, $to_file=NULL )
   {
     $this->halt_level = $halt_level;
     $this->log = array();
     $this->is_error = array();
     $this->milestone = $this->_getmicrotime();
     $this->_milestone = $this->milestone;
     $this->to_file = $to_file;
     $this->Trace("<b>log started.</b>",0);
   }

   // вывод лога
   function Flush( $prefix="<p><div>Trace log:</div><ul><li>", $separator="</li><li>", $postfix="</li></ul></p>" )
   {
     $this->Trace( "<b>log flushed.</b>",0);
     ob_start();
     echo $prefix;
     $f=0;
     foreach ($this->log as $item)
     {
      if (!$f) $f=1; else echo $separator;
      echo $item;
     }
     echo $postfix;
     if ($to_file) 
     { 
        $data = ob_get_contents(); ob_end_clean(); 
        $fp = fopen( $this->to_file ,"w");
        fputs($fp,$data);
        fclose($fp);
     }
     else ob_end_flush();
     $this->log = array();
   }

   // работа с временными отметками
   function _getmicrotime() { list($usec, $sec) = explode(" ",microtime());  return ((float)$usec + (float)$sec); }
   function Milestone( $what="" )
   {
     $m = $this->_getmicrotime();
     $diff = $m-$this->milestone;
     $this->Trace( "milestone (".sprintf("%0.4f",$diff)." sec): ".$what, 0 );
     $this->milestone = $m;
     return $diff;
   }

   // вывод в лог
   function Trace( $what, $flush=0 )
   {
     $m = $this->_getmicrotime();
     $diff = $m-$this->_milestone;
     $this->log[] = sprintf("[%0.4f] ",$diff).$what;
     if ($flush) $this->Flush();
   }

   // вывод в лог массива
   function Trace_R( $what, $flush=0, $title="trace recursive" )
   {
     ob_start();
     /*
      через это крюк обычно протаскивается всё окружение, причём есть циклические ссылки
      так что мы его отрубаем
    */
     unset($what->rh);
     print_r($what);
     $result = ob_get_contents();
     ob_end_clean();
     $suffix = substr(md5($result.rand()),0,6);
     $this->Trace("<b><a href='#' onclick='var a=document.getElementById(\"__tracediv".$suffix."\");a.style.display=(a.style.display==\"none\"?\"block\":\"none\"); return false;'>$title</a></b><div style='padding-left:60px; display:none' id='__tracediv".$suffix."'><pre style='color:#444444; font:11px Tahoma;margin:0'>".$result."</pre><b><a href=# onclick='var a=document.getElementById(\"__tracediv".$suffix."\");a.style.display=(a.style.display==\"none\"?\"block\":\"none\"); return false;'>Hide: $title</a></b></div>", $flush);
     return "<pre>".$result."</pre>";
   }

   // не пуст ли лог ошибок
   function IsError( $error_level = 1 )
   {
     if (isset($this->is_error[ $error_level ]))  return true;
     else return false;
   }

   // добавить в лог запись об ошибке и возможно умереть
   function Error( $msg, $error_level=1 )
   {
     $this->Trace( "<span style='font-weight:bold; color:#ff4000;'>[ERROR] ".$msg."</span>", 0 );
     for ($e=$error_level; $e>=0; $e--)
       $this->is_error[ $e ]=1;
     if ($this->IsError($this->halt_level)) $this->Halt();
   }

   // умереть, тихо или громко
   function Halt( $flush = 1 )
   {
     header("Content-Type: text/html; charset=windows-1251");
     if ($flush) $this->Flush();
     die("prematurely dying.");
   } 
      
   //перехватываем нефатальные ошибки PHP
   function ErrorHandler($errno, $errstr, $errfile, $errline){
     switch ($errno) { 
       case E_USER_ERROR: 
         $this->Error("[PHP ERROR] ($errno) $errstr");
         $this->Halt();
       break; 
       case E_USER_WARNING: 
         $this->Trace("[PHP WARNING] ($errno) $errstr");
       break; 
       case E_USER_NOTICE: 
         $this->Trace("[PHP NOTICE] ($errno) $errstr");
       break; 
       default: 
         $this->Trace("[PHP Unkown error type] ($errno) $errstr");
       break; 
     } 
   }

// EOC{ Debug } 
}



?>
