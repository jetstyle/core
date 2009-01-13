<?php
Finder::useClass('principal/session/PrincipalSessionInterface');

class PrincipalSessionPhp implements PrincipalSessionInterface
{
	protected $realm = "";
	protected $sessionKey = "principal_session";
	
	public function initSession()
	{		
		if (!session_id()) session_start();
	}
	
	public function getData()
	{
		return $_SESSION[$this->sessionKey.$this->realm];
	}
	
	public function getUserId()
	{
		return $_SESSION[$this->sessionKey.$this->realm]['user_id'];
	}
	
	public function delete()
	{
		$_SESSION[$this->sessionKey.$this->realm] = array();
	}
	
	public function start(&$storageModel = null)
	{
		$this->delete();
		
		if (null === $storageModel)
		{
			$userId = 0;
		}
		else
		{
			$userId = $storageModel->getId();
		}
		
		$_SESSION[$this->sessionKey.$this->realm] = array(
			'user_id' => $userId
		);
	}
	
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	public function updateLastActivity()
	{
		
	}
	
	public function setParams($params)
	{
		
	}
}

?>