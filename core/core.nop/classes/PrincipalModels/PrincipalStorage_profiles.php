<?php
/*

  Хранилище профилей принципала, основанная на структуре, подобной "гостевым профилям"

  PrincipalStorage_profiles( &$principal )
      - $principal       -- к какому принципалу прикрепляться

  -------------------

  Суть структуры:
    * формат профиля полностью идентичен "гостевому".
    * все профили хранятся в файлах:
       - principal_profiles/<login>.php           -- для логинов "пустого" рилма
       - principal_profiles/<realm>/<login>.php   -- для логинов рилма <realm>
    * карта соответствия id => login хранится:
       - principal_profiles/_storage_profiles.php -- как массив ( "login"=>..., "realm"=>... )

================================================================== v.1 (kuso@npj)
*/

class PrincipalStorage_profiles extends PrincipalStorage
{
   function PrincipalStorage_profiles( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;

     // find script or return
     $file_source = $this->rh->FindScript_( "principal_profiles", "_storage_profiles", false, -1 );
     // uplink
     include( $file_source );
     $this->profiles_hash = $included_profiles_hash;
   }

   function LoadById($id) 
   { 
     if (isset($this->profiles_hash[$id]))
        return $this->LoadByLogin( $this->profiles_hash[$id]["login"],
                                   $this->profiles_hash[$id]["realm"] );  
     else
        return false; 
   }
   function LoadByLogin($login, $realm="") 
   { 
     if ($realm != "") $login = $realm."/".$login;
     // find script or return
     $file_source = $this->rh->FindScript( "principal_profiles", $login, false, -1 );
     if ($file_source === false) return false;
     // uplink
     include( $file_source );
     return $included_profile; 
   }

// EOC{ PrincipalStorage_profiles }
}


?>