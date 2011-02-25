<?php
  $config = array(
    "wrapper_tpl"   => "wrapper.html:ImportantTextWrapper",
    "wrapper_title" => "[textarea title]",
    "validator_params" => array(
      "not_empty" => true,
    ),
    
    "interface_tpl" => "string.html:Textarea",
    "interface_tpl_params" => array(
                                    "cols" => 45,
                                    "rows" => 10,
                               ),
  );
?>