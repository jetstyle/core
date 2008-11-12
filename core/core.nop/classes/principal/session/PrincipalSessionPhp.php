<?php
Finder::useClass('principal/PrincipalSessionInterface');

class PrincipalSessionPhp implements PrincipalSessionInterface
{
	protected $data = null;
	protected $realm = "";
	
	public function __construct()
	{
		if (!session_id()) session_start();
	}
	
	public function initialize()
	{
		$this->data = $_SESSION['principal_session'];
	}
	
	public function getData()
	{
		return $this->data;
	}
	
	public function getUserId()
	{
		return $this->data['user_id'];
	}
	
	public function delete()
	{
		$_SESSION['principal_session'] = array();
	}
	
	public function start(&$storageModel)
	{
		$this->delete();
		$_SESSION['principal_session'] = array(
			'user_id' => $storageModel->getId()
		);
	}
	
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	public function updateLastActivity()
	{
		
	}
}

?>