<?php
Finder::useModel('DBModel');

class PrincipalCheat extends DBModel
{ 
	protected $one = true;
		
	public function getCheatHash($userId)
	{
		$stop = false;
		while (!$stop)
		{
			$hash = $this->generateHash();
			$this->loadOne('{hash} = '.self::quote($hash));
			if (!$this['hash'])
				$stop = true;
		}
		
		$data = array(
			'hash' => $hash,
			'user_id' => $userId,
			'timeline' => time()
		);
		$this->insert($data);
		return $hash;
	}
	
	public function loadByKey($key)
	{
		$this->loadOne('{hash} = '.self::quote($key));
	}
	
	public function increaseUsageCount()
	{
		$data = array('usage_count' => $this['usage_count'] + 1);
		$this->update($data, '{hash} = '.self::quote($this['hash']));
	}
	
	protected function generateHash()
	{
		return md5(time().$_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_HOST'].$_SERVER['HTTP_USER_AGENT'].$this->generateSalt());
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