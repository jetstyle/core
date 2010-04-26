<?php
/**
 * Module packer
 *
 * Grep tables, used in module.
 * Dump structure into .meta/structure.sql
 * Dump data into .meta/data.sql
 *
 * @author lunatic <lunatic@jetstyle.ru>
 */

Finder::useModel('DBModel');
class ModulePacker
{
	protected $db = null;
	protected $sqlDumper = null;

	public function __construct()
	{
		$this->db = Locator::get('db');
		Finder::useClass('ModuleConfig');
		Finder::useClass('ModuleConstructor');
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
			$modules = ModuleConstructor::getModulesList();
		}
		else
		{
			$modules = array($moduleName);
		}

		foreach ($modules AS $moduleDir)
		{
			$this->packModule($moduleDir);
		}
	}

	protected function packModule($moduleName)
	{
		$moduleDir = Config::get('app_dir').'modules/'.$moduleName;

		if (!file_exists($moduleDir) || !file_exists($moduleDir.'/.meta'))
		{
			return false;
		}
		$this->cleanUp($moduleDir);
		
		$config = array();
		if (file_exists($moduleDir.'/.meta/config.yml'))
		{
			$config = YamlWrapper::load($moduleDir.'/.meta/config.yml');
		}
		
		if (is_array($config['tables']))
		{
			$tables = $config['tables'];
		}
		else
		{
			$tables = $this->getTables($moduleName);
		}
		
		if (is_array($tables) && !empty($tables))
		{
			foreach ($tables AS $table)
			{
				$dumpData = false;
				
				if (isset($config['no_data']))
				{
					if (
						(is_array($config['no_data']) && !in_array($table, $config['no_data']))
						||
						(!is_array($config['no_data']) && !$config['no_data'])
					)
					{
						$dumpData = true;
					}
				}
				else
				{
					$dumpData = true;
				}
				
				$this->dumpStructure($table, $moduleDir, $dumpData);
				
				if ($dumpData)
				{
					$this->dumpData($table, $moduleDir);
				}
			}
		}
	}

	/**
	 * Дампим структуру таблицы в файл .meta/structure.sql
	 *
	 * @param string $tableName
	 * @param string $moduleDir
	 */
	protected function dumpStructure($tableName, $moduleDir, $dumpData = false)
	{
		$this->sqlDumper->dumpStructure($this->appendPrefixToTable($tableName), DBAL::$prefix, $moduleDir.'/.meta/structure.sql', $dumpData);
	}

	/**
	 * Дампим данные таблицы в файл .meta/structure.sql
	 *
	 * @param string $tableName
	 * @param string $moduleDir
	 */
	protected function dumpData($tableName, $moduleDir)
	{
		$this->sqlDumper->dumpData($this->appendPrefixToTable($tableName), DBAL::$prefix, $moduleDir.'/.meta/data.sql');
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
	}

	/**
	 * Получаем имена всех таблиц, используемых в модуле
	 *
	 * @param string $moduleDir
	 */
	protected function getTables($moduleName)
	{
		$module = ModuleConstructor::factory($moduleName);		
		$result = $this->getTablesRecursive($module);
		return array_unique($result);
	}

	protected function getTablesRecursive($module)
	{
		$result = array();
		$config = $module->getConfig();
		
		if (is_array($config) && $config['renderable'] && $config['model'])
		{
			$model = DBModel::factory($config['model']);
			$result[] = $model->getTableName();
		}
		
		$children = $module->getChildren();
		if (is_array($children))
		{
		    foreach ($children AS $child)
		    {
		        $result = array_merge($result, $this->getTablesRecursive($child));
		    }
		}
		
		return $result;
	}

	protected function appendPrefixToTable($v)
	{
		return trim(DBAL::$prefix.$v);
	}
}

?>