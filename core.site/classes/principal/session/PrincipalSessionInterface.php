<?php
/**
 * PrincipalSessionInterface
 * 
 * @author lunatic lunatic@jetstyle.ru
 *
 */
interface PrincipalSessionInterface
{
	/**
	 * Initialize session
	 *
	 */
	public function initSession();

	/**
	 * Get user id, stored in session
	 *
	 */
	public function getUserId();
	
	/**
	 * Delete current session
	 *
	 */
	public function delete($where = null);
	
	/**
	 * Cleanup (delete old sessions)
	 */
	public function cleanup();
	
	/**
	 * Start new session
	 *
	 * @param PrincipalStorage $storageModel
	 */
	public function start(&$storageModel = null);
	
	/**
	 * Set realm
	 *
	 * @param string $realm
	 */
	public function setRealm($realm);
	
	public function setParams($params);
	
}
?>
