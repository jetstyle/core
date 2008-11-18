<?php
/**
 * Locator.
 * 
 * The Service Locator pattern centralizes distributed service object lookups, 
 * provides a centralized point of control, and may act as a cache that eliminates redundant lookups. 
 * It also encapsulates any vendor-specific features of the lookup process.
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */
class Locator
{
	/**
	 * Objects cache
	 *
	 * @static
	 * @access private
	 * @var array
	 */
	private static $objs = array();
	
	/**
	 * Relations between keys and classes
	 *
	 * @static
	 * @access private
	 * @var array
	 */
	private static $relations = array();
	
	/**
	 * Static usage only.
	 * 
	 * @access private
	 */
	private function __construct(){	}
		
	/**
	 * Bind key and object.
	 *
	 * @param string $key
	 * @param string $path path to class OR object
	 * @param boolean $singleton
	 */
	public static function bind($key, $path, $singleton = true, $params = NULL)
	{
		Debug::trace('Bind "'.$key.'"', 'locator');
		if (is_object($path))
			self::$objs[$key] = $path;
		else
		{
			$class = pathinfo($path, PATHINFO_FILENAME);
			self::$relations[$key] = array(
				'class' => $class, 
				'path' => $path, 
				'singleton' => $singleton,
				'params' => $params
			);
		}
	}

    /**
     * Array wrapper for bind
     *
     * @param array $so
     */
	public static function bindArray($so)
	{
	    if (is_array($so))
	    {
		    foreach ($so AS $k => $s)
		    {
			    if (is_array($s))
			    {
				    if (!isset($s['singleton']))
				    {
					    $s['singleton'] = true;
				    }
				    Locator::bind($k, $s['path'], $s['singleton'], $s['params']);
			    }
			    else
			    {
				    Locator::bind($k, $s);
			    }
		    }
	    }
    }
    	
	/**
	 * Get object.
	 *
	 * @param string $key
	 * @return object
	 */
	public static function &get($key)
	{
		if (!isset(self::$objs[$key]))
		{
			if (isset(self::$relations[$key]))
			{
				Finder::useClass(self::$relations[$key]['path']);
				$class = self::$relations[$key]['class'];
				
				Debug::trace('Create "'.$key.'"', 'locator');
				
				if (self::$relations[$key]['singleton'])
				{
					eval('self::$objs[$key] = & '.$class.'::getInstance();');
				}
				else
				{
					if (null !== self::$relations[$key]['params'])
					{
						self::$objs[$key] = new $class(self::$relations[$key]['params']);
					}
					else
					{
						self::$objs[$key] = new $class();
					}
				}
			}
			else
			{
				throw new JSException('Object *'.$key.'* doesn\'t exist in Locator database');
			}
		}
		
		return self::$objs[$key];
	}
	
	/**
	 * Check existence of object 
	 *
	 * @param string $key
	 * @return boolean
	 */
	public static function exists($key)
	{
		return isset(self::$objs[$key]);
	}
}
?>
