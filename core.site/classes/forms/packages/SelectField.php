<?php
  $config = array(
//    "wrapper_tpl"   => "wrapper.html:Row",
    "extends_from"   => "OptionsField",
    "wrapper_title" => "[select title]",
    
    "view"          => "options",
    
    "interface"     => "options",
    "interface_tpl" => "options.html:Select",
    
    "options_mode" => "select", // select, radio
    "options"       => array(),
    "interface_tpl_params" => array( "class" => "" ),
//    "model_default" => 1,
    "wrapper_tpl"   => "wrapper.html:DefaultStringWrapper",
  );
?>