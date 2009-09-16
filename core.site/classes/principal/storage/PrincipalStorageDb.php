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
	
	public function insert(&$row)
	{
		$id = parent::insert($row);
		if ($this->updateSupertag)
		{
			$this->updateSupertag = null;
			$data = array('_supertag' => $row['_supertag'].$id);
			$this->update($data, "{".$this->getPk()."} = ".DBModel::quote($id));
		}
		return $id;
	}
	
	protected function onBeforeInsert(&$row)
	{
		$row['_created'] = date('Y-m-d H:i:s');
		
		$salt = $this->generateSalt();
		$row['salt'] = $salt;
		$row['password'] = md5(md5($row['password']).$salt);

		Finder::useClass('Translit');
		$translit = new Translit();
		$supertag = $translit->supertag( $row['login'], TR_NO_SLASHES, 100);

		// check supertag
		$model = clone $this;
		$model->loadOne('{_supertag} = '.self::quote($supertag)." AND {realm} = ".self::quote($this->realm));
		if ($model[$model->getPk()])
		{
			$this->updateSupertag = true;
		}
		
		$row['_supertag'] = $supertag;
		
		parent::onBeforeInsert($row);
	}
	
	
	
	protected function onBeforeUpdate(&$row)
	{
		if ($row['password'] == '') unset($row['password']);
		
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