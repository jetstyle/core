<?php
interface PrincipalSecurityInterface
{
	const GRANTED = true;
	const DENIED = false;
	
	public function check( &$userData, $params="" );
//	public function getUserACL($userId);
//	public function getGroupACL($groupId);
//	public function updateGroupACL($groupId, $acl);
//	public function updateUserACL($userId, $acl);
}
?>