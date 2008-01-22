<?
/*
  Замещать инстансы модулей незаметно для врапперов
*/
  
class Dummy {
  
  var $config;
  
  function Dummy( &$config ){
    $this->config =& $config;
  }
  
  function Handle(){}
  
} 
?>