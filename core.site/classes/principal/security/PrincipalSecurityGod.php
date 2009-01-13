<?php
Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityGod implements PrincipalSecurityInterface
{
	public function check( &$userData, $params="" )
	{
		$status = self::DENIED;
		if ($userData['group']['god']) 
		{
			$status = self::GRANTED;
		}
		
		$debugText = 'Access to module '.$params.': '.($status ? "<span style=\"color: green;\">GRANTED</span>" : "<span style=\"color: red;\">DENIED</span>");
		Debug::trace($debugText, 'principal');
		return $status;
	}
}
?>