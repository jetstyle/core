<?php

   $rh->UseLib("Translit", "php/translit");

   // text берём из параметров, которые нам даёт Rockette
   if (!is_array($params)) $params = array("_"=>$params);
   $text = $params["_"]?$params["_"]:$params[0];

   echo Translit::DeWikify( $text );

?>