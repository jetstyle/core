<?php
/*

   онтроль доступа "√ќ—“яћ Ќ≈Ћ№«я"

  PrincipalSecurity_noguests( &$principal )

  -------------------

================================================================== v.1 (kuso@npj)
*/
//die(noguests);
class PrincipalSecurity_noguests extends PrincipalSecurity
{
   function Check( &$user_data, $params="" )  // denied by default
   { 
     if ($user_data["login"] == "guest") return DENIED;
     else return GRANTED;
   }

// EOC{ PrincipalSecurity_noguests }
}


?>