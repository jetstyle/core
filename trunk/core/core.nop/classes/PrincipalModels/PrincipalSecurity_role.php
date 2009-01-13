<?php
/*

  Контроль доступа "ТОЛЬКО ДЛЯ РОЛИ $params"

  PrincipalSecurity_role( &$principal )

  -------------------

================================================================== v.1 (kuso@npj)
*/
//die(roles security model);
class PrincipalSecurity_role extends PrincipalSecurity
{
   function OnLogin( &$user_data )
   { 
     $roles = explode(" ",$user_data["roles"]);
     $roles_ = array();
     foreach( $roles as $role )
      if ($role != "")
       $roles_[ $role ] = 1;
     $user_data["_security_role_cache"] = $roles_;

     return true;
   }
	
   function Check( &$user_data, $params="" )  // denied by default
   { 
     if (isset($user_data["_security_role_cache"][$params])) return GRANTED;
     else return DENIED;
   }

// EOC{ PrincipalSecurity_role }
}


?>