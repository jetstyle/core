<?php
  $config = array(
            "model"     => "group",
            "interface" => "group",
            "validator" => "group",
            "view"      => "group",
            
            "group_tpl"       => "group.html:List",
            "wrapper_collapsed" => false,
            
            "interface_tpl_params" => array( "class" => "w100" ),
            
            "wrapper"   => "wrapper_group",
            "wrapper_tpl"     => "wrapper.html:DefaultGroupWrapper",
            "wrapper_title" => "",
            "wrapper_desc"  => "",
  );
?>