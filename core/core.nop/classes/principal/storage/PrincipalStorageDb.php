<?php
Finder::useClass('principal/storage/PrincipalStorageInterface');
Finder::useModel('DBModel');

class PrincipalStorageDb extends DBModel implements PrincipalStorageInterface
{
	protected $realm = "";
	protected $cookieSalt = "it is a good day to die"; 
	protected $one = true;
		
	public function setParams($params)
	{
		if ($params['salt'])
		{
			$this->cookieSalt = $params['salt'];
		}
	}
	
	public function loadById($id)
	{
		$this->loadOne('{id} = '.self::quote($id)." AND {realm} = ".self::quote($this->realm));		
	}
	
	public function loadByLogin($login)
	{
		$this->loadOne('{login} = '.self::quote($login)." AND {realm} = ".self::quote($this->realm) );
	}
	
	public function checkPassword($password, $fromCookie = false)
	{
		$userPassword = $this->get('password');
		if (!$fromCookie)
		{
			$password = md5(md5($password).$this->get('salt'));
		}
		else
		{
			$userPassword = md5($userPassword.$this->cookieSalt);
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
	
	public function get($key)
	{
		return $this[$key];
	}
	
	public function getId()
	{
		return $this->get('id');
	}

	public function getCookiePassword()
	{
		return md5($this->get('password').$this->cookieSalt);
	}
	
	public function getLogin()
	{
		return $this->get('login');
	}
	
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	public function guest()
	{
		$this->setData(array(array('id' => 0, 'login' => 'guest')));
	}
	
	protected function onBeforeInsert(&$row)
	{
		$salt = $this->generateSalt();
		$row['salt'] = $salt;
		$row['password'] = md5(md5($row['password']).$salt);
		
		parent::onBeforeInsert($row);
	}
	
	protected function onBeforeUpdate(&$row)
	{
		if (array_key_exists('password', $row))
		{
			$salt = $this->generateSalt();
			$row['salt'] = $salt;
			$row['password'] = md5(md5($row['password']).$salt);
		}
		
		parent::onBeforeUpdate($row);
	}
	
	protected function generateSalt()
	{
		$salt = '';
		for ($i = 0; $i < 3; $i++)
		{
			$salt .= chr(rand(32, 126));
		}
		return $salt;
	}
}

?>