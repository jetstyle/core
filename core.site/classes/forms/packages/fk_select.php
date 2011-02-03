<?php
  $config = array(
//    "wrapper_tpl"   => "wrapper.html:Row",
    "wrapper_title" => "[select title]",
    
    "view"          => "options",
    
    "interface"     => "options",
    "interface_tpl" => "options.html:Select",
    
    "options_mode" => "select", // select, radio
    "options"       => array(),
    "interface_tpl_params" => array( "class" => "" ),
    "model" => "model_fk_select",
    "wrapper_tpl"=> "wrapper.html:CmsStringWrapper"
//    "model_default" => 1,
  );
?>
