<?php
/**
 * ��������� �������
 * 
 * �������� �������, ������� ������������ � ������
 * ������ ��������� ������ � .meta/structure.sql
 * ������ ������ ������ � .meta/data.sql
 * ������ ������� �� ������� Toolbar � .meta/toolbar.sql  
 * 
 */

class ModulePacker
{
	protected $rh;
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
	}
	
	/**
	 * �����������
	 * ���� ��� ������ �� �������, ������ ��� ������
	 * 
	 * @param string $moduleName - optional
	 */
	public function pack($moduleName = null)
	{
		if (null === $moduleName)
		{
			$modules = $this->getModulesList();
		}
		else
		{
			$modules = array($this->getModule($moduleName));
		}
		
		foreach ($modules AS $module)
		{
			$this->packModule($module);
		}
	}
	
	protected function packModule($module)
	{
		var_dump($module);
		die();
	}
	
	protected function getModulesList()
	{
		return $this->rh->db->query("
			SELECT * 
			FROM ??toolbar
			WHERE LENGTH(href) > 0
		");
	}
	
	protected function getModule($href)
	{
		return $this->rh->db->queryOne("
			SELECT * 
			FROM ??toolbar
			WHERE href = ".$this->rh->db->quote($href)." AND LENGTH(href) > 0
		");
	}
	
}

?>