<?php
/*

  Контроль доступа "ТОЛЬКО ПО IP"

  PrincipalSecurity_ip( &$principal )

  -------------------

  uses

  $rh->principal_security_ip = array ( "192.168.1.5", "192.168.1.5", // by ip
                                       or
                                       "192.168.0.0/255.255.0.0" // ip+mask


================================================================== v.1 (kuso@npj)
*/

class PrincipalSecurity_ip extends PrincipalSecurity
{
   function Check( &$user_data, $params="" )  // denied by default
   { 
     $iip = explode(".",$_SERVER["REMOTE_ADDR"]);
     $digital_ip = 0;
     for($i=0; $i<sizeof($iip); $i++)
       $digital_ip = $digital_ip*256 + $iip[$i];

     Debug::Trace( $digital_ip );

     foreach( $this->rh->principal_security_ip as $zone )
     {
       $ip = explode("/",$zone);

       $imask = explode(".",$ip[1]);
       $digital_mask = 0;
       for($i=0; $i<sizeof($imask); $i++)
        $digital_mask = $digital_mask*256 + $imask[$i];

       if (sizeof($ip) == 1) $_ip = $digital_ip & 0xffffffff;
       else                  $_ip = $digital_ip & $digital_mask;

       $imask2 = explode(".",$ip[0]);
       $digital_mask2 = 0;
       for($i=0; $i<sizeof($imask2); $i++)
        $digital_mask2 = $digital_mask2*256 + $imask2[$i];

       $digital_mask2 = $digital_mask2 & 0xffffffff;

       Debug::Trace( "($digital_mask) -> ".$digital_mask2 ." == ". $_ip );

       if ($_ip == $digital_mask2) return GRANTED;
     }
     return DENIED;
   }

// EOC{ PrincipalSecurity_ip }
}


?>