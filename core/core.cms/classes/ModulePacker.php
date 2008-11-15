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
			$modules = array($moduleName);
		}

		foreach ($modules AS $moduleDir)
		{
			$this->packModule($moduleDir);
		}
	}

	protected function packModule($moduleDir)
	{
		$moduleDir = Config::get('app_dir').'modules/'.$moduleDir;

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
			$tables = $this->getTables($moduleDir);
		}
		
		if (is_array($tables) && !empty($tables))
		{
			foreach ($tables AS $table)
			{
				$this->dumpStructure($table, $moduleDir);
				if (isset($config['no_data']))
				{
					if (
						(is_array($config['no_data']) && !in_array($table, $config['no_data']))
						||
						(!is_array($config['no_data']) && !$config['no_data'])
					)
					{
						$this->dumpData($table, $moduleDir);
					}
				}
				else
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
	protected function dumpStructure($tableName, $moduleDir)
	{
		$this->sqlDumper->dumpStructure($this->appendPrefixToTable($tableName), DBAL::$prefix, $moduleDir.'/.meta/structure.sql');
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
	protected function getTables($moduleDir, $configName = 'defs')
	{
		$result = array();
		
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
			$result[] = $tableName;
		}

		return array_unique($result);
	}

	protected function getModulesList()
	{
		$result = array();
		
		if ($handle = opendir(Config::get('app_dir').'modules')) 
		{
		    while (false !== ($file = readdir($handle))) 
		    {
		        if ($file != "." && $file != "..") 
		        {
		            $result[] = $file;
		        }
		    }
		    closedir($handle);
		}
		
		return $result;
	}

	protected function appendPrefixToTable($v)
	{
		return trim(DBAL::$prefix.$v);
	}
}

?>