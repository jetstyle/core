<?php
/*
 �������� ������� "������ ������"
 */

Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityOpenid implements PrincipalSecurityInterface
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