<?php

class Principal
{	 
	const RESTORED = -1;
	const AUTH = 0;
	const WRONG_LOGIN = 1;
	const WRONG_PWD = 2;
	const ALREADY_AUTH = 3;
	const NO_CREDENTIALS = 4;
	const NOT_IDENTIFIED = 100;
	
	
	protected $storageModel = null;
	protected $sessionModel = null;
	protected $securityModels = array();

	protected $realm = '';
	
	public function __construct($params = array())
	{
		$storageModelType = "profiles";		
		$sessionModelType = 'php';
		
		if ($params['storageModelType'])
		{
			$storageModelType = $params['storageModelType'];
		}
		
		if ($params['sessionModelType'])
		{
			$sessionModelType = $params['sessionModelType'];
		}
		
		if ($params['realm'])
		{
			$this->setRealm($params['realm']);
		}
		
		//storage model
		$className = "PrincipalStorage".ucfirst($storageModelType);
		Finder::useClass('principal/storage/'.$className);
		$this->storageModel = new $className();
		if (!in_array('PrincipalStorageInterface', class_implements($this->storageModel)))
		{
			throw new JSException($className.' must implement \'PrincipalStorageInterface\'');
		}
		$this->storageModel->setRealm($this->realm);
		
		// session model
		$className = "PrincipalSession".ucfirst($sessionModelType);
		Finder::useClass('principal/session/'.$className);
		$this->sessionModel = new $className();
		if (!in_array('PrincipalSessionInterface', class_implements($this->sessionModel)))
		{
			throw new JSException($className.' must implement \'PrincipalSessionInterface\'');
		}
		$this->sessionModel->setRealm($this->realm);
		$this->sessionModel->initialize();
		
		$this->identify();
	}

	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	public function get($field)
	{
		return $this->storageModel[$field];
	}
	
	public function getId()
	{
		return $this->storageModel->getId();
	}
	
	protected function identify()
	{		
		$userId = $this->sessionModel->getUserId();		
		if ($userId)
		{
			$this->storageModel->loadById($userId);
			if (!$this->storageModel->getId())
			{
				$userId = 0;
				$this->storageModel->guest();
				$this->sessionModel->start($this->storageModel);
			}
		}
		
		// try to login, using login and pass from cookies
		list($login, $password) = $this->getLoginAndPassFromCookies();
		if ($login && $password)
		{
			if (!$this->login($login, $password, true))
			{
				$this->deleteLoginAndPassFromCookies();
			}
		}
		else
		{
			$this->storageModel->guest();
		}
		
		$this->sessionModel->updateLastActivity();
	}
	
	public function security( $model, $params="" )
	{
		$sm = &$this->getSecurityModel($model);
		return $sm->check( $this->storageModel, $params );
	}
	
	public function login( $login="", $pwd="", $fromCookie = false)
	{
		if (!$login || !$pwd)
		{
			return self::WRONG_LOGIN;
		}
		
		// already logged in
		if ($this->security('noguests'))
		{
			return self::ALREADY_AUTH;
		}
		
		// load user
		$this->storageModel->loadByLogin( $login );
		if (!$this->security('noguests'))
		{
			$this->storageModel->guest();
			return self::WRONG_LOGIN;
		}
 		
		$state = null;
		
		if ($this->storageModel->checkPassword($pwd, $fromCookie))
		{
			$state = self::AUTH;
		}
		else
		{
			$state = self::WRONG_PWD;
			$this->storageModel->guest();
		}
		
		$this->sessionModel->start($this->storageModel);
		return $state;
	}
	
	public function logout()
	{
		$this->storageModel->guest();
		$this->sessionModel->start($this->storageModel);
	}
	
	protected function getLoginAndPassFromCookies()
	{
		return array($_COOKIE[Config::get('cookie_prefix').$this->realm.'principal_login'], $_COOKIE[Config::get('cookie_prefix').$this->realm.'principal_password']);
	}
	
	protected function setLoginAndPassToCookie()
	{
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_login', $this->storageModel->getLogin(), 0, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_password', md5($this->storageModel->getPassword().$this->salt), 0, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
	}
	
	protected function deleteLoginAndPassFromCookies()
	{
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_login', '', 0, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_password', '', 0, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
	}
	
	protected function &getSecurityModel($model)
	{
		if (!isset($this->securityModels[$model]))
		{
			$className = "PrincipalSecurity".ucfirst($model);
			Finder::useClass('principal/security/'.$className);
			$this->securityModels[$model] = new $className();
			if (!in_array('PrincipalSecurityInterface', class_implements($this->securityModels[$model])))
			{
				throw new JSException($className.' must implement \'PrincipalSecurityInterface\'');
			}
		}
		
		return $this->securityModels[$model];
	}
}
?>