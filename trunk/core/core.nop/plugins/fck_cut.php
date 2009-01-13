<?php
  // not implemented in R1
  if (!is_array($params)) $params = array("_"=>$params);
  $text = $params["_"]?$params["_"]:$params[0];

  $text = explode("[cut]", $text);
  echo $text[0];
?>