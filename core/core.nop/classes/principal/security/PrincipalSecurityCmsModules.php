<?php
Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityCmsModules implements PrincipalSecurityInterface
{
	protected $ACL = array();
	protected $groupACL = array();
	
	protected $aclCache = array();
	
	protected $tableUsersAccess = "??users_access";
	protected $tableGroupsAccess = "??users_groups_access";
	
	public function check( &$storageModel, $params="" )
	{
		$status = self::DENIED;
		$location = strtolower($params);
		if ($storageModel->getId() > 0 && strlen($location) > 0) 
		{
			$userData = $storageModel->getData();
			if ($userData['group']['god'])
			{
				$status = self::GRANTED;
			}
			else
			{
				if (array_key_exists($location, $this->aclCache))
				{
					$status = $this->aclCache[$location];
				}
				else
				{
					$ACL = $this->getACL($storageModel);
					if (is_array($ACL) && !empty($ACL))
					{				
						foreach($ACL AS $loc => $v)
						{
							if(strpos($location, $loc.'/') === 0 || $loc === $location)
							{
								$status = self::GRANTED;
								break;
							}
						}
					}
					$this->aclCache[$location] = $status;
				}
			}
		}
		
		$debugText = 'Access to module '.$params.': '.($status ? "<span style=\"color: green;\">GRANTED</span>" : "<span style=\"color: red;\">DENIED</span>");
		Debug::trace($debugText, 'modules access');
		return $status;
	}
	
	public function getUserACL($userId)
	{
		if (!array_key_exists($userId, $this->ACL))
		{
			$this->loadUserACL($userId);
		}
		return $this->ACL[$userId];
	}
	
	public function getGroupACL($groupId)
	{
		if (!array_key_exists($groupId, $this->groupACL))
		{
			$this->loadGroupACL($groupId);
		}
		return $this->groupACL[$groupId];
	}
	
	public function updateGroupACL($groupId, $acl)
	{
		$groupId = intval($groupId);
		$this->deleteGroupACL($groupId);
		
		if (is_array($acl) && !empty($acl))
		{
			$db = Locator::get('db');
			foreach($acl AS $nodeId)
			{
				$db->execute("
               		INSERT INTO ".$this->tableGroupsAccess." 
               		(group_id, node_id)
               		VALUES 
               		(".$groupId.", ".intval($nodeId).")
               	");
			}
		}
	}
	
	public function updateUserACL($userId, $acl)
	{
		$userId = intval($userId);
		$this->deleteUserACL($userId);
		
		if (is_array($acl) && !empty($acl))
		{
			$db = Locator::get('db');
			foreach($acl AS $nodeId)
			{
				$db->execute("
               		INSERT INTO ".$this->tableUsersAccess." 
               		(user_id, node_id)
               		VALUES 
               		(".$userId.", ".intval($nodeId).")
               	");
			}
		}
	}
	
	public function deleteUserACL($userId)
	{
		$userId = intval($userId);
		$db = Locator::get('db');
		
		$db->execute("
			DELETE FROM ".$this->tableUsersAccess." 
			WHERE user_id = ".$userId."
		");
	}
	
	public function deleteGroupACL($groupId)
	{
		$groupId = intval($groupId);
		$db = Locator::get('db');
		
		$db->execute("
			DELETE FROM ".$this->tableGroupsAccess." 
			WHERE group_id = ".$groupId."
		");
	}
	
	protected function getACL($storageModel)
	{
		$userData = $storageModel->getData();
		if ($userData['group']['custom'])
		{
			return $this->getUserACL($storageModel->getId());
		}
		else
		{
			return $this->getGroupACL($userData['group']['id']);
		}
	}
	
	protected function loadUserACL($userId)
	{
		$db = &Locator::get('db');
		
		$result = $db->execute("
			SELECT t.href 
			FROM ".$this->tableUsersAccess." AS ua 
			LEFT JOIN ??toolbar AS t ON(ua.node_id = t.id) 
			WHERE ua.user_id = ".$db->quote($userId)." AND t._state IN(0,1) AND LENGTH(t.href) > 0 
		");

		$acl = array();
		
		while($r = $db->getRow($result))
		{
			$acl[strtolower($r['href'])] = true;
		}
		
		$this->ACL[$userId] = $acl;
	}
	
	protected function loadGroupACL($groupId)
	{
		$db = &Locator::get('db');
		
		$result = $db->execute("
			SELECT t.href 
			FROM ".$this->tableGroupsAccess." AS ua 
			LEFT JOIN ??toolbar AS t ON(ua.node_id = t.id) 
			WHERE ua.group_id = ".$db->quote($groupId)." AND t._state IN(0,1) AND LENGTH(t.href) > 0 
		");

		$acl = array();
		
		while($r = $db->getRow($result))
		{
			$acl[strtolower($r['href'])] = true;
		}
		
		$this->groupACL[$groupId] = $acl;
	}
}
?>