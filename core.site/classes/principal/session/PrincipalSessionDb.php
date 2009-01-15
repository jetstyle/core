<?php
Finder::useClass('principal/session/PrincipalSessionInterface');
Finder::useModel('DBModel');

class PrincipalSessionDb extends DBModel implements PrincipalSessionInterface
{
	protected $one = true;
	
	protected $realm = "";
	protected $expireTime = 900;
	protected $sessionHash = '';
	protected $idHash = '';
	protected $ip = '';
	
	protected $sessionData = array();
	
	public function initSession()
	{		
		$this->where = ($this->where ? $this->where." AND " : "")." {last_activity} > ".(time() - $this->expireTime);
		
		if ($this->getSessionHash())
		{
			$this->load("{session_hash} = ".DBModel::quote($this->getSessionHash())." AND {id_hash} = ".DBModel::quote($this->getIdHash()));
		}
		
		if (!$this->offsetGet('session_hash'))
		{
			$this->load("{user_id} = 0 AND {id_hash} = ".DBModel::quote($this->getIdHash()));
			
			if (!$this->offsetGet('session_hash'))
			{
				$this->start();
			}
		}
		
		if ($this->offsetGet('session_hash'))
		{
			$this->sessionHash = $this->offsetGet('session_hash');
			$this->saveSessionHash();
			$this->updateLastActivity();
		}
	}
	
	public function setParams($params)
	{
		if (isset($params['expireTime']))
		{
			$this->expireTime = $params['expireTime'];
		}
	}
	
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}
	
	
	public function get($key)
	{
		return $this->sessionData[$key];
	}

	public function set($key, $value = '')
	{
		if (is_array($key))
		{
			foreach ($key AS $k => $v)
			{
				$this->sessionData[$k] = $v;
			}
		}
		else
		{
			$this->sessionData[$key] = $value;
		}
		
		if (!$this->getSessionHash())
		{
			$this->start();
		}
		
		$data = array('data' => serialize($this->sessionData));
		parent::update($data, "{session_hash} = ".DBModel::quote($this->getSessionHash()));
	}
	
	public function getUserId()
	{
		return $this->offsetGet('user_id');
	}
	
	public function &load($where=NULL, $limit=NULL, $offset=NULL)
	{
		parent::load($where, $limit, $offset);
		
		$data = $this->offsetGet('data');
		$data = unserialize($data);
		if (is_array($data))
		{
			$this->sessionData = $data;
		}
	}
	
	public function delete()
	{
		if ($this->getSessionHash())
		{
			parent::delete("{session_hash} = ".DBModel::quote($this->getSessionHash()));
			$this->sessionHash = '';
			$this->setData(array(array()));
			$this->sessionData = array();
		}
	}
	
	public function cleanup()
	{
		parent::delete("{last_activity} < ".(time() - $this->expireTime));
	}
	
	public function start(&$storageModel = null)
	{
		$this->delete();
		
		if (null === $storageModel)
		{
			$userId = 0;
		}
		else
		{
			$userId = $storageModel->getId();
		}
		
		$this->sessionHash = $this->generateSessionHash();
		$this->saveSessionHash();
		
		$data = array(
			"session_hash" => $this->sessionHash,
			"id_hash" => $this->getIdHash(),
			"user_id" => $userId,
			"host" => $this->getIp(),
			"user_agent" => $this->getUserAgent(),
			"last_activity" => time()
		);
		
		$this->insert($data);
		$this->setData(array($data));
	}

	protected function saveSessionHash()
	{
		if ($this->expireTime)
		{
			$expireTime = time() + $this->expireTime;
		}
		else
		{
			$expireTime = 0;
		}
		
		setcookie(Config::get('cookie_prefix').$this->realm.'session_hash', $this->getSessionHash(), $expireTime, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
	}
	
	protected function updateLastActivity()
	{
		if ($this->getSessionHash())
		{
			$data = array('last_activity' => time());
			parent::update($data, "{session_hash} = ".DBModel::quote($this->getSessionHash()));
		}
	}
	
	protected function generateSessionHash()
	{
		return md5(time() . $this->getUserAgent() . $this->getIp() . rand(1, 1000000));
	}
		
	protected function getSessionHash()
	{
		if (!$this->sessionHash)
		{
			$this->sessionHash = $_COOKIE[Config::get('cookie_prefix').$this->realm."session_hash"] ? $_COOKIE[Config::get('cookie_prefix').$this->realm."session_hash"] : "";
		}
		
		return $this->sessionHash;
	}
	
	protected function getIdHash()
	{
		if (!$this->idHash)
		{
			$this->idHash = md5($this->getIp().$this->getUserAgent());
		}
		return $this->idHash; 
	}
	
	protected function getIp()
	{
		if (!$this->ip)
		{
			if ($_SERVER['HTTP_CLIENT_IP'])
			{
				$this->ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			else if ($_SERVER['HTTP_X_FORWARDED_FOR'] && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
			{
				// make sure we dont pick up an internal IP defined by RFC1918
				foreach ($matches[0] AS $ip)
				{
					if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip))
					{
						$this->ip = $ip;
						break;
					}
				}
			}
			else if ($_SERVER['HTTP_FROM'])
			{
				$this->ip = $_SERVER['HTTP_FROM'];
			}
			else
			{
				$this->ip = $_SERVER['REMOTE_ADDR'];
			}
		}

		return $this->ip;
	}
	
	protected function getUserAgent()
	{
		return $_SERVER['HTTP_USER_AGENT'];
	}
}

?>