<?php
Finder::useClass('principal/security/PrincipalSecurityInterface');

class PrincipalSecurityContent implements PrincipalSecurityInterface
{
	protected $ACL = array();
	protected $groupACL = array();
	
	protected $aclCache = array();
	
	protected $tableUsersAccess = "??users_content_access";
	protected $tableGroupsAccess = "??users_groups_content_access";
	
	public function check( &$storageModel, $params="" )
	{
		$status = self::DENIED;

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
					if (is_array($ACL) && $ACL[$params])
					{				
						$status = self::GRANTED;
						break;
					}
					$this->aclCache[$params] = $status;
				}
			}
		}
		
		$debugText = 'Access to content node '.$params.': '.($status ? "<span style=\"color: green;\">GRANTED</span>" : "<span style=\"color: red;\">DENIED</span>");
		Debug::trace($debugText, 'content access');
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
		$db = Locator::get('db');
		
		$db->execute("
			DELETE FROM ".$this->tableGroupsAccess." 
			WHERE group_id = ".$groupId."
		");
		
		if (is_array($acl) && !empty($acl))
		{
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
		$db = Locator::get('db');
		
		$db->execute("
			DELETE FROM ".$this->tableUsersAccess." 
			WHERE user_id = ".$userId."
		");
		
		if (is_array($acl) && !empty($acl))
		{
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
		
		$acl = $db->query("
			SELECT t.id 
			FROM ".$this->tableUsersAccess." AS ua 
			LEFT JOIN ??content AS t ON(ua.node_id = t.id) 
			WHERE ua.user_id = ".$db->quote($userId)." AND t._state IN(0,1) 
		", "id");
				
		$this->ACL[$userId] = $acl;
	}
	
	protected function loadGroupACL($groupId)
	{
		$db = &Locator::get('db');
		
		$acl = $db->query("
			SELECT t.id 
			FROM ".$this->tableGroupsAccess." AS ua 
			LEFT JOIN ??content AS t ON(ua.node_id = t.id) 
			WHERE ua.group_id = ".$db->quote($groupId)." AND t._state IN(0,1) 
		", "id");
		
		$this->groupACL[$groupId] = $acl;
	}
}
?>