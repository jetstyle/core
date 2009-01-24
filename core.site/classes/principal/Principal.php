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
		return $this->storageModel->getId();
	}
	
	public function getUserData()
	{
		return $this->storageModel->getData();
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
	}
	
	public function security( $model, $params="" )
	{
		$sm = &$this->getSecurityModel($model);
		return $sm->check( $this->storageModel, $params );
	}
	
	public function loginOpenidStart($login)
	{
	    if (!$login )
	    {
		return self::WRONG_LOGIN;
	    }
	    	
	    $r = Finder::useLib("SimpleOpenID");
	    $openid = new SimpleOpenID();
	    $openid->SetIdentity($login);
	    $openid->SetTrustRoot(  RequestInfo::$baseFull );
	    $openid->SetRequiredFields(array());//'fullname'
	    //$openid->SetOptionalFields(array('dob','gender','postcode','country','language','timezone'));
	    if ($openid->GetOpenIDServer())
	    {
		$redirectTo = RequestInfo::get('retpath') ?
					  RequestInfo::get('retpath') :
					  RequestInfo::$baseFull."login";
					  
		$openid->SetApprovedURL( $redirectTo );      // Send Response from OpenID server to this script
		$openid->Redirect();     // This will redirect user to OpenID Server
	    }
	    else
	    {
		#$error = $openid->GetError();
		#echo "ERROR CODE: " . $error['code'] . "<br>";
		#echo "ERROR DESCRIPTION: " . $error['description'] . "<br>";
		return self::NO_CREDENTIALS;
	    }
	    exit;
	}
	
	public function loginOpenidProceed()
	{
		Finder::useLib("SimpleOpenID");
	    
		$openid = new SimpleOpenID;
		$openid->SetIdentity($_GET['openid_identity']);
		
		$openid_validation_result = $openid->ValidateWithServer();

		//var_dump( $openid->OpenID_Standarize($_GET['openid_identity']));die();
		if ($openid_validation_result == true)
		{         // OK HERE KEY IS VALID

		    $normalized_login = $openid->OpenID_Standarize( $_GET['openid_identity'] );
		    //var_dump($normalizied_login);
		
		    //[ ] check user in db
		    $this->storageModel->loadByOpenidUrl( $normalized_login );
    		    if(! $this->storageModel->getId() )
    		    {
    			//[ ] new User
			$newPass = md5(time().$normalized_login);
			$newUser = array(
				'group_id'=>2,
				'login'=>$normalized_login,
				'password'=>$newPass,
				'realm'=>'site',
				'openid_url'=> $normalized_login
			);
    			$userId = $this->storageModel->insert($newUser);
			
			$state = $this->login($normalized_login, $newPass, true);
    		    } 
    		    else 
    		    {
			$this->setLoginAndPassToCookies();
			$state = self::AUTH;
			$this->sessionModel->start($this->storageModel);

    		    }
	
		    return $state;
		}
		else if($openid->IsError() == true)
		{
		   // ON THE WAY, WE GOT SOME ERROR
		    #$error = $openid->GetError();
		    #echo "ERROR CODE: " . $error['code'] . "<br>";
		    #echo "ERROR DESCRIPTION: " . $error['description'] . "<br>";
		    return self::NO_CREDENTIALS;
		}
		else
		{                                            // Signature Verification Failed
		    #echo "INVALID AUTHORIZATION";
		   return self::NO_CREDENTIALS;
		}
    
		die();
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
