<?php
interface PrincipalStorageInterface
{
	public function getId();
	public function getCookiePassword();
	public function getLogin();
	
	public function loadById($id);
	public function loadByLogin($login);
	public function setRealm($realm);
	
	public function guest();
	
	public function checkPassword($password, $fromCookie = false);
}
?>