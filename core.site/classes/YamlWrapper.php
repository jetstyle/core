<?php
/**
 * Yaml loader with cache support
 *  
 * @package yaml
 * @author lunatic <lunatic@jetstyle.ru>
 * @since version 0.4 
 */
final class YamlWrapper
{
	/**
	 * Cache object
	 */
	private static $cacher = null;
	
	/**
	 * Static use only.
	 * @access private
	 */
	private function __construct(){}
	
	/**
	 * Load YAML config.
	 * 
	 * @static
	 * @access public
	 * @param string $filePath
	 * @return array
	 */
	public static function load($filePath)
	{		
		if (!file_exists($filePath))
		{
			$e = new FileNotFoundException("File not found: <b>".$filePath."</b>", '');
			$e->setFilename($filePath);
			throw $e;
		}
		$result = array();
		
		$cacher = self::getCacher();
		$cacher->setFile(self::getCachedName($filePath));
		$cacher->addSource($filePath);
				
		if ($cacher->isValid())
		{
			$result = include $cacher->getFileName();
			$result = unserialize($result);
		}
		else
		{
			Finder::useLib('spyc');
			$result = Spyc :: YAMLLoad($filePath);
			$cacher->write("return '".str_replace("'", "\\'", serialize($result))."';");
		}
		
		return $result;
	}
	
	/**
	 * Save Php array as YAML config.
	 * 
	 * @static
	 * @access public
	 * @param string $filePath
	 * @param array  $array - php array
	 */
	public static function save($filePath, $array)
	{		
		if (!file_exists($filePath) && is_writeable($filePath) )
		{
			$e = new FileNotFoundException("File not found or not writeable: <b>".$filePath."</b>", '');
			$e->setFilename($filePath);
			throw $e;
		}
		$result = array();

		Finder::useLib('spyc');
		$result = Spyc :: YAMLDump($array);

		file_put_contents($filePath, $result);

		return $result;
	}
	/**
	 * Get cacher object.
	 * 
	 * @return object cache
	 */
	private static function getCacher()
	{
		if (null === self::$cacher)
		{
			self::$cacher = new FileCache();
		}
		
		return self::$cacher;
	}
	
	/**
	 * Get cached filename
	 * 
	 * @return string
	 */
	private static function getCachedName($filePath)
	{
		return preg_replace("/[^\w\x7F-\xFF\s]/", '_', str_replace(Config::get('project_dir'), '', $filePath)).'.php';
	}
}
?>