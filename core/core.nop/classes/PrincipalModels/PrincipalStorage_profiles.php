<?php
/*

  ��������� �������� ����������, ���������� �� ���������, �������� "�������� ��������"

  PrincipalStorage_profiles( &$principal )
      - $principal       -- � ������ ���������� �������������

  -------------------

  ���� ���������:
    * ������ ������� ��������� ��������� "���������".
    * ��� ������� �������� � ������:
       - principal_profiles/<login>.php           -- ��� ������� "�������" �����
       - principal_profiles/<realm>/<login>.php   -- ��� ������� ����� <realm>
    * ����� ������������ id => login ��������:
       - principal_profiles/_storage_profiles.php -- ��� ������ ( "login"=>..., "realm"=>... )

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