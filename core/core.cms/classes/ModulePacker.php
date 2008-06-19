<?php
/**
 * Упаковщик модулей
 * 
 * Собираем таблицы, которые используются в модуле
 * Дампим структуру таблиц в .meta/structure.sql
 * Дампим данные таблиц в .meta/data.sql
 * Дампим строчку из таблицы Toolbar в .meta/toolbar.sql  
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
	 * Упаковываем
	 * Если имя модуля не указано, пакуем все модули
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