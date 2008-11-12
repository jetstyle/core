<?php
interface PrincipalSecurityInterface
{
	const GRANTED = true;
	const DENIED = false;
	
//	public function onRestore( &$user_data );
//	public function onLogin( &$user_data );
//	public function onGuest( &$user_data );
	public function check( &$userData, $params="" );
}
?>