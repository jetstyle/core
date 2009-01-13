<?php
/**
 * FileManager
 * 
 * @package config
 * @author lunatic <lunatic@jetstyle.ru>
 * @since version 0.4 
 */
Finder::useClass('File');
class FileManager
{
	private static $instance = null;
	
	private $configs = array();
	private $keys2paths = array();
	
	private function __construct(){}
	
	protected static function &getInstance()
	{
		if (null === self::$instance)
			self::$instance = new self();
		
		return self::$instance;
	}
	
	/**
	 * Get file by key AND id, if exists
	 *
	 * @param string $key
	 * @param int $id
	 * @param bool $isFileId
	 * @return File object
	 */
	public static function getFile($key, $id = 0, $isFileId = false)
	{
		if ($key)
		{
			$keyParts = explode(':', $key);
			$config = self::getConfig($keyParts[0], $keyParts[1]);
		}
		else
		{
			$isFileId = true;
			$config = array();
		}
		
		$file = new File($config);
		
		if ($id)
		{
			if ($isFileId)
			{
				$file->setId($id);
			}
			else
			{
				$file->setObjId($id);
			}
		}
		
		return $file;
	}

	public static function getConfig($conf, $key = null)
	{
		return self::getInstance()->getConfigInternal($conf, $key);
	}
	
	private function getConfigInternal($conf, $key = null)
	{
		if (!array_key_exists($conf, $this->configs))
		{
			$this->loadConfig($conf);
		}

		if ($key)
		{
			$keyParts = explode('/', $key);
			if (count($keyParts) == 2)
			{
				return $this->configs[$conf][$keyParts[0]]['children'][$keyParts[1]];
			}
			else
			{
				return $this->configs[$conf][$keyParts[0]];
			}
		}
		else
		{
			return $this->configs[$conf];
		}
	}
	
	private function getPathsForKey($key)
	{
		if (!$this->keys2paths[$key])
		{
			$keyParts = explode('/', $key);
			$moduleName = array_shift($keyParts);
			
			$path = Config::get('project_dir').'cms/modules/'.$moduleName.'/';
			
			if (count($keyParts))
			{
				$path .= 'conf/'.implode('/', $keyParts).'/';
			}
			
			$path .= 'files.yml';
			$this->keys2paths[$key] = $path;
		}

		return $this->keys2paths[$key];
	}
	
	private function loadConfig($conf)
	{
		$path = $this->getPathsForKey($conf);
		try
		{
			$this->configs[$conf] = YamlWrapper::load($path);
		}
		catch (FileNotFoundException $e)
		{
			$this->configs[$conf] = array();
		}
		
		if (!empty($this->configs[$conf]))
		{
			foreach ($this->configs[$conf] AS $key => &$v)
			{
				if (is_array($v))
				{
					$children = array();
					foreach ($v AS $subKey => $subConf)
					{
						if ($subKey{0} == '>')
						{
							$_subKey = substr($subKey, 1);
							$children[$_subKey] = array(
								'actions' => $subConf,
								'key' => $key,
								'subkey' => $subKey,
								'conf' => $conf
							);
							
							if (is_array($subConf))
							{
								$children[$_subKey]['actions_hash'] = md5(serialize($subConf));
							}
							unset($v[$subKey]);
						}
					}
					$v['children'] = $children;
				}
				
				$v['key'] = $key;
				$v['conf'] = $conf;
				if (is_array($v['actions']))
				{
					$v['actions_hash'] = md5(serialize($v['actions']));
				}
			}
		}
	}
}
?>