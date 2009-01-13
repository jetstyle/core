<?php
/*
  онтроль доступа "√ќ—“яћ Ќ≈Ћ№«я"
 */

Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityNoguests implements PrincipalSecurityInterface
{
	public function check( &$userData, $params="" )  // denied by default
	{
		if ($userData->getId() > 0) 
		{
			return self::GRANTED;
		}
		else 
		{
			return self::DENIED;
		}
	}
}
?>