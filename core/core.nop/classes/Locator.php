<?php

class Locator
{
	private static $objs = array();
	private static $relations = array();
	
	/**
	 * Static usage only.
	 * 
	 * @access private
	 */
	private function __construct(){	}
	
	public static function bind($key, $path, $static = true)
	{
		Debug::trace('Bind "'.$key.'"', 'locator');
		if (is_object($path))
			self::$objs[$key] = $path;
		else
		{
			$class = pathinfo($path, PATHINFO_FILENAME);
			self::$relations[$key] = array('class' => $class, 'path' => $path, 'static' => $static);
		}
	}
	
	public static function &get($key)
	{
		if (!isset(self::$objs[$key]))
		{
			if (isset(self::$relations[$key]))
			{
				Finder::useClass(self::$relations[$key]['path']);
				$class = self::$relations[$key]['class'];
				
				Debug::trace('Create "'.$key.'"', 'locator');
				
				if (self::$relations[$key]['static'])
				{
					eval('self::$objs[$key] = & '.$class.'::getInstance();');
				}
				else
				{
					self::$objs[$key] = new $class();
				}
			}
			else
			{
				throw new JSException('Object *'.$key.'* doesn\'t exist in Locator database');
			}
		}
		
		return self::$objs[$key];
	}
	
	public static function exists($key)
	{
		return isset(self::$objs[$key]);
	}
}
?>