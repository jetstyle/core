<?php

Finder::useClass('principal/PrincipalInterface');

class Principal implements PrincipalInterface
{
	protected $storageModel = null;
	protected $sessionModel = null;
	protected $securityModels = array();

	protected $realm = '';
//	protected $permanentStore = false;
	protected $permanentExpireTime = 1209600;	// two weeks
	
	public function __construct($params = array())
	{
		$storageModelType = "db";		
		$sessionModelType = 'php';
		
		if ($params['storage']['model'])
		{
			$storageModelType = $params['storage']['model'];
		}
		
		if ($params['session']['model'])
		{
			$sessionModelType = $params['session']['model'];
		}
		
		if ($params['realm'])
		{
			$this->realm = $params['realm'];
		}
		
		//storage model
		$className = "PrincipalStorage".ucfirst($storageModelType);
		Finder::useClass('principal/storage/'.$className);
		$this->storageModel = new $className();
		if (!in_array('PrincipalStorageInterface', class_implements($this->storageModel)))
		{
			throw new JSException($className.' must implement \'PrincipalStorageInterface\'');
		}
		if (isset($params['storage']))
		{
			$this->storageModel->setParams($params['storage']);
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
		if (isset($params['session']))
		{
			$this->sessionModel->setParams($params['session']);
		}
		$this->sessionModel->setRealm($this->realm);
		$this->sessionModel->initSession();
		
		$this->identify();
	}

	public function getStorageModel()
	{
		return $this->storageModel;	
	}
	
	public function getSessionModel()
	{
		return $this->sessionModel;
	}
	
	public function get($field)
	{
		return $this->storageModel->get($field);
	}
	
	public function getId()
	{
	    $contest = ContestsModel::getCurrentContest();
	    $id = false;
	    if (!$contest['may_all_users']) {
	        $id = $this->storageModel->getId();
	    }
		if (!$id) {
		    //setcookie('user_id', $id, -1);
		    if ($_COOKIE['user_id']) {
		        $id = $_COOKIE['user_id'];
		        //setcookie('user_id', $id, -1);
		    }
		    else {
                $ip = ($_SERVER["HTTP_X_FORWARDED_FOR"]!="") ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
                $ip = abs(sprintf("%u",ip2long($ip)));
		        $id = abs((time() + $ip) / 100);
		        setcookie('user_id', $id, time() + 60*60*24*365, '/', '.' . RequestInfo::$baseDomain);
		    }
		}
		echo '<pre>'; print_r( $id ); echo '</pre>'; die();
		return $id;
	}
	
	public function getUserData()
	{
		return $this->storageModel->getData();
	}
		
	public function security( $model, $params="" )
	{
		$sm = &$this->getSecurityModel($model);
		return $sm->check( $this->storageModel, $params );
	}
	
	public function cheatLogin( $key )
	{
		if (!$key)
		{
			return self::WRONG_LOGIN;
		}
		
		Finder::useClass('principal/PrincipalCheat');
		$cheatModel = new PrincipalCheat();
		$cheatModel->loadByKey($key);
		
		if (!$cheatModel['user_id'])
		{
			return self::NO_CREDENTIALS;
		}
		
		// already logged in
		if ($this->security('noguests'))
		{
			if ($cheatModel['user_id'] == $this->storageModel->getId() )
			{
				return self::ALREADY_AUTH;
			}
			else
			{
				$this->logout();
			}
		}
				
		$cheatModel->increaseUsageCount();
		
		// load user
		$this->storageModel->loadById( $cheatModel['user_id'] );
		if (!$this->security('noguests'))
		{
			$this->storageModel->guest();
			return self::WRONG_LOGIN;
		}
 		
		$this->sessionModel->start($this->storageModel);
		return self::AUTH;
	}
	
	public function login( $login="", $pwd="", $permanent = false, $fromCookie = false)
	{
		if (!$login || !$pwd)
		{
			return self::WRONG_LOGIN;
		}
		
		// already logged in
		if ($this->security('noguests'))
		{
			$this->logout();
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
			if ($permanent)
			{
				$this->setLoginAndPassToCookies();
			}
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
		$this->deleteLoginAndPassFromCookies();
		$this->sessionModel->start($this->storageModel);
	}
	
	public function getCheatHash($userId = 0)
	{
		Finder::useClass('principal/PrincipalCheat');
		$cheatModel = new PrincipalCheat();
		
		if (!$userId)
		{
			$userId = $this->getStorageModel()->getId();
		}
		
		return $cheatModel->getCheatHash($userId);
	}
	
	protected function identify()
	{
		$userId = $this->sessionModel->getUserId();		
		if ($userId > 0)
		{
			$this->storageModel->loadById($userId);
			if (!$this->storageModel->getId())
			{
				$userId = 0;
				
				// make user guest, start new guest session
				$this->storageModel->guest();
				$this->sessionModel->start($this->storageModel);
			}
		}
		
		if (!$userId)
		{
			// try to login, using login and pass from cookies
			list($login, $password) = $this->getLoginAndPassFromCookies();
			if ($login && $password)
			{
				if ($this->login($login, $password, false, true) !== PrincipalInterface::AUTH)
				{
					$this->deleteLoginAndPassFromCookies();
				}
			}
			else
			{
				$this->storageModel->guest();
			}
		}
		
		// auto login key
		$key = RequestInfo::get('autologinkey');
		if ($key && strlen($key) == 32)
		{
			$this->cheatLogin($key);
			Controller::redirect(RequestInfo::hrefChange('', array('autologinkey' => '')));
		}
	}
	
	protected function getLoginAndPassFromCookies()
	{
		return array($_COOKIE[Config::get('cookie_prefix').$this->realm.'principal_login'], $_COOKIE[Config::get('cookie_prefix').$this->realm.'principal_password']);
	}
	
	protected function setLoginAndPassToCookies()
	{
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_login', $this->storageModel->getLogin(), time() + $this->permanentExpireTime, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
		setcookie(Config::get('cookie_prefix').$this->realm.'principal_password', $this->storageModel->getCookiePassword(), time() + $this->permanentExpireTime, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
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