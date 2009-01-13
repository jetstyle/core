<?php

interface PrincipalInterface
{
	const RESTORED = -1;
	const AUTH = 0;
	const WRONG_LOGIN = 1;
	const WRONG_PWD = 2;
	const ALREADY_AUTH = 3;
	const NO_CREDENTIALS = 4;
	const NOT_IDENTIFIED = 100;
	
//	public function setRealm($realm);
	public function get($field);
	public function getId();
	public function getUserData();
	public function getStorageModel();
	public function getSessionModel();
	public function security( $model, $params="" );
	public function login( $login="", $pwd="", $permanent = false, $fromCookie = false);
	public function logout();
}

?>