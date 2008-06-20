<?php
/**
 * Упаковщик модулей
 * 
 * Собираем таблицы, которые используются в модуле
 * Дампим структуру таблиц в .meta/structure.sql
 * Дампим данные таблиц в .meta/data.sql
 * Дампим строчку из таблицы Toolbar в .meta/toolbar.sql  
 *
 * @author lunatic lunatic@jetstyle.ru
 * @created 19.06.2008
 *  
 */

class ModulePacker
{
	protected $rh;
	protected $sqlDumper = null;
	
	public function __construct(&$rh)
	{
		$this->rh = &$rh;
		$this->rh->useClass('ModuleConfig');
		$this->rh->useClass('sql/SqlDump');
		$this->sqlDumper = new SqlDump($this->rh);
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
		$moduleDir = $this->rh->app_dir.'modules/'.$module['href'];
		
		if (!file_exists($moduleDir))
		{
			return false;
		}
		
		$this->cleanUp($moduleDir);
		$tables = $this->getTables($moduleDir);
		
		if (is_array($tables) && !empty($tables))
		{
			foreach ($tables AS $table)
			{
				$this->dumpStructure($table, $moduleDir);
				$this->dumpData($table, $moduleDir);
			}
		}

		$this->dumpModuleRow($module);
	}
	
	/**
	 * Дампим структуру таблицы в файл .meta/structure.sql
	 *
	 * @param string $tableName
	 * @param string $moduleDir
	 */
	protected function dumpStructure($tableName, $moduleDir)
	{
		$this->sqlDumper->dumpStructure($tableName, $moduleDir.'/.meta/structure.sql');
	}
	
	/**
	 * Дампим данные таблицы в файл .meta/structure.sql
	 *
	 * @param string $tableName
	 * @param string $moduleDir
	 */
	protected function dumpData($tableName, $moduleDir)
	{
		$this->sqlDumper->dumpData($tableName, $moduleDir.'/.meta/data.sql');
	}
	
	protected function dumpModuleRow()
	{
		
	}
	
	/**
	 * Удаляем старые файлы 
	 *
	 * @param string $dir
	 */
	protected function cleanUp($dir)
	{
		@unlink($dir.'/.meta/structure.sql');
		@unlink($dir.'/.meta/data.sql');
		@unlink($dir.'/.meta/toolbar.sql');
	}
	
	/**
	 * Получаем имена всех таблиц, используемых в модуле
	 *
	 * @param string $moduleDir
	 */
	protected function getTables($moduleDir, $configName = 'defs')
	{
		$result = array();
				
		$config = new ModuleConfig($this->rh);
		$config->read($moduleDir.'/'.$configName.'.php');
		
		$wrapped = $config->get('WRAPPED');
		
		if (is_array($wrapped) && !empty($wrapped))
		{
			foreach ($wrapped AS $wrap)
			{
				$result = array_merge($result, $this->getTables($moduleDir, $wrap));
			}
		}
		elseif ($tableName = $config->get('table_name'))
		{
			$result[] = $this->rh->db_prefix.$tableName;
		}
		 
		return array_unique($result);
	}
		
	protected function getModulesList()
	{
		return $this->rh->db->query("
			SELECT * 
			FROM ??toolbar
			WHERE LENGTH(href) > 0 AND _state IN (0,1)
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