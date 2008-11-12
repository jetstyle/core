<?php
Finder::useModel('DBModel');
Finder::useClass('principal/PrincipalStorageInterface');

class PrincipalStorageDb extends DBModel implements PrincipalStorageInterface
{
	protected $realm = "";
	
	protected $table = "users";
	
	protected $fields = array(
		'id',
		'login',
		'password',
		'salt'
	);
	
	public $where = '{_state} = 0';
	
	protected $one = true;
	
	protected $cookieSalt = "it is good day to die"; 
	
	public function __construct($fieldSet = null)
	{
		parent::__construct($fieldSet);
		
		if (Config::exists('principal_salt'))
		{
			$this->cookieSalt = Config::get('principal_salt');
		}
	}
	
	public function loadById($id)
	{
		self::loadOne('{id} = '.DBModel::quote($id)." AND {realm} = ".DBModel::quote($this->realm));		
	}
	
	public function loadByLogin($login)
	{
		self::loadOne('{login} = '.DBModel::quote($login)." AND {realm} = ".DBModel::quote($this->realm) );
	}
	
	public function checkPassword($password, $fromCookie = false)
	{
		$userPassword = $this->offsetGet('password');
		if (!$fromCookie)
		{
			$password = md5(md5($password).$this->offsetGet('salt'));
		}
		else
		{
			$userPassword = md5($userPassword.$this->salt);
		}
		
		if ($userPassword === $password)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function getId()
	{
		return $this->offsetGet('id');
	}

//	public function getPassword()
//	{
//		return $this->offsetGet('password');
//	}

	public function getCookiePassword()
	{
		return md5($this->offsetGet('password').$this->cookieSalt);
	}
	
	public function getLogin()
	{
		return $this->offsetGet('login');
	}
	
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	public function guest()
	{
		$this->setData(array(array('id' => 0, 'login' => 'guest')));
	}
}


?>