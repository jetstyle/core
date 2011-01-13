<?php
  $config = array(
            "model"     => "group",
            "interface" => "group",
            "validator" => "group",
            "view"      => "group",
            
            "group_tpl"       => "group.html:CmsList",
            "wrapper_collapsed" => false,
            
            "interface_tpl_params" => array( "class" => "w100" , "closed"=> "true" ),
            
            "wrapper"   => "wrapper_group",
            "wrapper_tpl"   => "wrapper.html:CmsDefaultGroupWrapper",
            "wrapper_title" => "",
            "wrapper_desc"  => "",
            "group_title"   => "seo",
/*
  doesn`t work now, feature request: http://in.jetstyle.ru/quickstart/formstories
            "fields"=>array(
                "meta_title"=>array(),
                "meta_keywords"=>array(),
                "meta_description"=>array()
                ),
*/
  );
?>
