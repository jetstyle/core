<?php
interface PrincipalSessionInterface
{
	public function initialize();

	/**
	 * get session data
	 *
	 */
	public function getData();
	
	public function getUserId();
	
	/**
	 * delete current session
	 *
	 */
	public function delete();
	
	public function start(&$storageModel);
	
	public function setRealm($realm);
	
	public function updateLastActivity();
	
}
?>