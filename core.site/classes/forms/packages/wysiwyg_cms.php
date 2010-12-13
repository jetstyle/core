<?php
  $config = array(
    "wrapper_tpl"   => "wrapper.html:CmsWYSIWYGStringWrapper",
    "wrapper_title" => "[textarea title]",
    
    "interface_tpl" => "string.html:WYSIWYG",
    "model"=> "model_filters",
    "model_filters"=>array(
          "a"=> "typografica"),
    "model_filtered"=>array(
          "a"=> "*_pre")
  );
?>
