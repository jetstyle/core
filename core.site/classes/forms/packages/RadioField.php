<?php
  $config = array(
//    "wrapper_tpl"   => "wrapper.html:Row",
    "extends_from"   => "OptionsField",
    "wrapper_title" => "[radio title]",
    
    "view"          => "options",
    
    "interface"     => "options",
    "interface_tpl" => "options.html:Radio",
    
    "options_mode" => "radio", // select, radio
    "options"       => array(),
    "model_default" => 2,
    
    "wrapper_tpl"   => "wrapper.html:DefaultStringWrapper",
  );
?>