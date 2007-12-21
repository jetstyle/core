<?php
/*

  Хранилище профилей принципала, основанная на таблице в БД

  PrincipalStorage_db( &$principal )
      - $principal       -- к какому принципалу прикрепляться

  -------------------

  Суть структуры:
    * каждый профиль -- строка в соотв. таблице
    * имя таблицы можно перезадать в 
       $rh->principal_storage_model_table = "users"

================================================================== v.1 (kuso@npj)
*/
class PrincipalStorage_db extends PrincipalStorage
{
   var $table = "users";

   function PrincipalStorage_db( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;

     if (isset($rh->principal_storage_model_table))
      $this->table = $rh->principal_storage_model_table;

     $this->db_table = $this->rh->db_prefix.$this->table;
   }

   function LoadById($id) 
   { 
     $sql = "select * from ".$this->db_table.
            " where active=1 and user_id=".$this->rh->db->Quote($id);
     $user_data = $this->rh->db->QueryOne( $sql );


     if ($user_data === false) return false;

     return $user_data;
   }
   function LoadByLogin($login, $realm="") 
   { 
     $sql = "select * from ".$this->db_table.
            " where active=1 ".
            " and login=".$this->rh->db->Quote($login).
            " and realm=".$this->rh->db->Quote($realm);
     
     $user_data = $this->rh->db->QueryOne( $sql );
	
     if ($user_data === false) return false;

     return $user_data;
   }

   function SetStoredPassword( $user_data, $new_invariant )
   { 
     $login = $user_data["login"];
     $realm = $user_data["realm"];
     $sql = "update ".$this->db_table.
            " set stored_invariant = ".$this->rh->db->Quote($new_invariant).
            " where ".
            "     login=".$this->rh->db->Quote($login).
            " and realm=".$this->rh->db->Quote($realm);
     $this->rh->db->Query( $sql );
     return true; 
   }

   function _SetLoginDatetime( $user_data, $datetime )
   {
     $login = $user_data["login"];
     $realm = $user_data["realm"];
     $dt = date("Y-m-d H:i:s", $datetime );
     $sql = "update ".$this->db_table.
            " set login_datetime = ".$this->rh->db->Quote($dt).
            " where ".
            "     login=".$this->rh->db->Quote($login).
            " and realm=".$this->rh->db->Quote($realm);
     $this->rh->db->Query( $sql );
     return true; 
   }


// EOC{ PrincipalStorage_db }
}


?>