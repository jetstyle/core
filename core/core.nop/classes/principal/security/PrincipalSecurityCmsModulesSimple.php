<?php
Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityCmsModulesSimple implements PrincipalSecurityInterface
{
	public function check( &$userData, $params="" )
	{
		$status = self::DENIED;
		if ($userData->getId() > 0) 
		{
			$status = self::GRANTED;
		}
		
		$debugText = 'Access to module '.$params.': '.($status ? "<span style=\"color: green;\">GRANTED</span>" : "<span style=\"color: red;\">DENIED</span>");
		Debug::trace($debugText, 'modules access');
		return $status;
	}
}
?>