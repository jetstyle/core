<?php

   $rh->UseLib("Translit", "php/translit");

   // text ���� �� ����������, ������� ��� ��� Rockette
   if (!is_array($params)) $params = array("_"=>$params);
   $text = $params["_"]?$params["_"]:$params[0];

   if (isset($params["allow_slashes"]))
     $allow_slashes = TR_ALLOW_SLASHES;
   else
     $allow_slashes = TR_NO_SLASHES;

   echo Translit::Supertag( $text, $allow_slashes );

?>