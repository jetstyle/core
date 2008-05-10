<?php
/*
  Замещать инстансы модулей незаметно для врапперов
*/
  
class Dummy 
{
  protected $config;
  
  public function __construct( &$config ){
    $this->config =& $config;
  }
  
  public function getHtml(){}
  
  public function handle(){}
  
} 
?>