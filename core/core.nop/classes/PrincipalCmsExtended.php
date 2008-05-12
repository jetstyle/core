<?php
$this->UseClass("PrincipalCms");

class PrincipalCmsExtended extends PrincipalCms
{	
	protected $userDefaultHandlers = array('start');
	
	public function getId()
	{
		return $this->user[$this->id_field];
	}

	public function getUserRole()
	{
		return ($this->ROLES_REVERT[$this->user['roles']]);
	}

	protected function _GetBy($where)
	{
		$db = & $this->rh->db;
		$user = $db->queryOne('' .
				'SELECT u.user_id, u.group_id, u.city_id, u.login, u.password, u.title, u.premoderate, ug.title AS group_title, ug.god AS group_god, ug.custom AS group_custom ' .
				'FROM ' . $this->users_table . ' AS u ' .
				'LEFT JOIN ??users_groups AS ug ON (u.group_id = ug.id) ' .
				'WHERE u._state = 0 AND ug._state = 0 AND ' . $where);

		$this->ACL = array(); 
		
		if ($user[$this->id_field])
		{	
			// свой доступ у каждого пользователя
			if($user['group_custom'])
			{
				// toolbar
				$result = $this->rh->db->query("" .
					"SELECT t.href " .
					"FROM ??users_access AS ua " .
					"LEFT JOIN ??toolbar AS t ON(ua.node_id = t.id) " .
					"WHERE ua.user_id = ".$user[$this->id_field]." AND t._state IN(0,1) AND LENGTH(t.href) > 0 " .
				"");
				
				if($result)
				{
					while($r = $this->rh->db->getRow($result))
					{
						$this->ACL[$r['href']] = true;
					}
				}
				
				// content
				$res = $this->rh->db->query("" .
					"SELECT c.id " .
					"FROM ??users_content_access AS ua " .
					"LEFT JOIN ??content AS c ON(ua.node_id = c.id) " .
					"WHERE ua.user_id = ".$user[$this->id_field]." AND c._state IN(0,1,2) " .
				"");
				
				if(is_array($res) && !empty($res))
				{
					foreach($res AS $r)
					{
						$this->ACL_CONTENT[$r['id']] = true;
					}
				}
				
			}
			// общий доступ для группы
			elseif(!$user['group_god'])
			{
				// toolbar
				$res = $this->rh->db->query("" .
					"SELECT t.href " .
					"FROM ??users_groups_access AS ua " .
					"LEFT JOIN ??toolbar AS t ON(ua.node_id = t.id) " .
					"WHERE ua.group_id = ".$user['group_id']." AND t._state IN(0,1) AND LENGTH(t.href) > 0 " .
				"");
				
				if(is_array($res) && !empty($res))
				{
					foreach($res AS $r)
					{
						$this->ACL[$r['href']] = true;
					}
				}
				
				// content
				$res = $this->rh->db->query("" .
					"SELECT c.id " .
					"FROM ??users_groups_content_access AS ua " .
					"LEFT JOIN ??content AS c ON(ua.node_id = c.id) " .
					"WHERE ua.group_id = ".$user[$this->id_field]." AND c._state IN(0,1,2) " .
				"");
				
				if(is_array($res) && !empty($res))
				{
					foreach($res AS $r)
					{
						$this->ACL_CONTENT[$r['id']] = true;
					}
				}
				
			}
			return $user;
		} 
		else
		{
			return false;
		}
	}

	/*
	 * принципал билдера не хотел логинить с md5
	 */
	function Login($login, $password)
	{
		//пытаемся загрузить пользователя по логину
		if (!($this->user = $this->GetByLogin($login)))
		{
			$this->user = $this->GetByID(0);
			$this->state = PRINCIPAL_WRONG_LOGIN;
//			$this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - неверный логин</font> ");
			return false;
		}

		//проверяем пароль 
		if ($this->user['password'] != md5($password))
		{
			$this->user = $this->GetByID(0);
			$this->state = PRINCIPAL_WRONG_PWD;
//			$this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - неверный пароль</font>");
			return false;
		}

		//сохраняем пользователя в сессии
//		$this->rh->debug->Trace("<font color='green'>Principal::Login('$login','$password') - OK</font>");
		$this->SessionStore();
		$this->state = PRINCIPAL_AUTH;

		return true;
	}
	
	function isGod()
	{
		if($this->user['group_god'] == 1)
		{
			return true;
		}
	}
	
	public function isGrantedTo($location)
	{
		if(!$this->isAuth())
		{
			return false;
		}
		
		// режим бога?
		if($this->isGod()) 
		{
			return true;
		}
		
		$location = strtolower($location);
		
		if(in_array($location, $this->userDefaultHandlers)) return true;
				
		if(strlen($location) > 0)
		{
			foreach($this->ACL AS $loc => $v)
			{
				$loc = strtolower($loc);
				if(strpos($location, $loc.'/') === 0 || $loc === $location)
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
}
?>