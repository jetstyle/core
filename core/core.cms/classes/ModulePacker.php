<?php
/**
 * Module packer
 *
 * Grep tables, used in module.
 * Dump structure into .meta/structure.sql
 * Dump data into .meta/data.sql
 * Dump toolbar row into .meta/toolbar.sql
 *
 * @author lunatic <lunatic@jetstyle.ru>
 */
class ModulePacker
{
	protected $db = null;
	protected $sqlDumper = null;

	public function __construct()
	{
		$this->db = &Locator::get('db');
		Finder::useClass('ModuleConfig');
		Finder::useClass('sql/SqlDump');
		$this->sqlDumper = new SqlDump();
	}

	/**
	 * Pack.
	 *
	 * If module name is empty, than pack all modules.
	 * 
	 * @param string $moduleName
	 */
	public function pack($moduleName = null)
	{
		if (null === $moduleName)
		{
			$modules = $this->getModulesList();
		}
		else
		{
			$module = $this->getModule($moduleName);
			if (!is_array($module) || empty($module))
			{
				return;
			}
			$modules = array($module);
		}

		foreach ($modules AS $module)
		{
			$this->packModule($module);
		}
	}

	protected function packModule($module)
	{
		$moduleDir = Config::get('app_dir').'modules/'.$module['href'];

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
		//TODO: how to do this ?
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
		
		if (file_exists($moduleDir.'/.meta/tables'))
		{
			$result = file($moduleDir.'/.meta/tables', FILE_SKIP_EMPTY_LINES);
			if ($result === false)
			{
				return array();
			}
			else
			{
				return array_map(array(&$this, 'appendPrefixToTables'), $result);
			}
		}

		$config = new ModuleConfig();
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
			$result[] = DBAL::$prefix.$tableName;
		}

		return array_unique($result);
	}

	protected function getModulesList()
	{
		return $this->db->query("
			SELECT *
			FROM ??toolbar
			WHERE LENGTH(href) > 0 AND _state IN (0,1)
		");
	}

	protected function getModule($href)
	{
		$module = $this->db->queryOne("
			SELECT *
			FROM ??toolbar
			WHERE href = ".$this->db->quote($href)." OR href LIKE ".$this->db->quote($href.'/%')." AND LENGTH(href) > 0
		");
		
		if ($module['href'] != $href)
		{
			$module['href'] = $href;
		}
		
		return $module;
	}

	protected function appendPrefixToTables($v)
	{
		return trim(DBAL::$prefix.$v);
	}
}

?>