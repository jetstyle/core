<?php
/**
 * Config class for working with config files.
 * 
 * Config files has YAML struct
 * 
 * @package config
 * @author lunatic <lunatic@jetstyle.ru>
 * @since version 0.4 
 */
final class Config
{
	/**
	 * Datastore
	 * 
	 * @static
	 * @access private
	 * @var array
	 */
	private static $data = array();
	
	/**
	 * Static use only
	 */
	private function __construct(){}
	
	/**
	 * Get data from store
	 * 
	 * @static
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		return self::$data[$key];
	}
	
	/**
	 * Set data to store
	 * 
	 * @static
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		self::$data[$key] = $value;
	}
	
	/**
	 * Load data from source (file, array, db).
	 * Source determined automatically
	 * 
	 * @param mixed $src
	 * @return boolean
	 */
	public static function load($src)
	{
		$result = false;

		if (is_array($src))
		{
			$result = self::loadFromArray($src);
		}
		else if (is_file($src))
		{
			$result = self::loadFromFile($src);
		}
		else
		{
			$result = self::loadFromDb($src);
		}
		
		return $result;
	}
	
	/**
	 * Load data from file.
	 * 
	 * @param string $fileName
	 * @return boolean
	 */
	public static function loadFromFile($fileName)
	{
		return self::loadFromArray(YamlWrapper::load($fileName));
	}
	
	/**
	 * Load data from Db.
	 * 
	 * @param string $tableName
	 * @return boolean
	 */
	public static function loadFromDb($tableName)
	{
		$db = &DBAL::getInstance();
		$result = $db->execute("SELECT name, value FROM ".$tableName." WHERE _state = 0");
		
		while ($r = $db->getRow($result))
		{
			self::$data[$r['name']] = $r['value'];
		}
		
		return true;
	}
	
	/**
	 * Load data from array.
	 * 
	 * @param array $array
	 * @return boolean
	 */
	public static function loadFromArray($array)
	{
		if (!is_array($array))
		{
			return false;
		}
		
		foreach ($array AS $key => $value)
		{
			self::$data[$key] = $value;
		}
		
		return true;
	}
	
	public static function dump()
	{
		vaR_dump(self::$data);
	}
}

?>