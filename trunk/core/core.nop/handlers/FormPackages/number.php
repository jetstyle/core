<?php
  $config = array(
    "interface_tpl" => "string.html:Small",
     
    "view_tpl"      => "Plain", // ???? kuso@npj: WTF
//    "view_prefix"    => "от",
//    "view_postfix"   => "страниц",
//    "view_wrap_interface" => true,
    
//    "wrapper_tpl"   => "wrapper.html:Row",
//    "wrapper_title" => "[number title]",
//    "wrapper_desc"  => "[number desc]",
    
    "validator" => "validator_string",
    "validator_params" => array( "is_numeric" => 1 ),
//    "model_default" => 20,
  );
?>