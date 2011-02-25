<?php
  $config = array(
    "extends_from" => "DateField",
    "interface_tpl" => "string.html:CmsInsertedString",
    "wrapper_tpl"   => "wrapper.html:CmsInsertedStringWrapper",
    "update_fields"   => array("year", "month", "day")
  );
  
?>
