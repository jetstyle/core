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
	 * 
	 */
	private static $stacks = array();
	
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
	public static function bind($key, $path, $singleton = false, $params = NULL)
	{
		Debug::trace('Bind "'.$key.'"', 'locator');
		if (is_object($path) || is_array($path))
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
	 * Reset bindings, for Unit Tests only
	 *
	 */
	public static function reset()
	{
		foreach (self::$relations as $key=>$rel)
		{
		    if ($rel["singleton"] && method_exists(self::$objs[$key], "delete") )
			self::$objs[ $key ]->delete();
		}

		self::$relations = array();
		self::$objs = array();
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
					    $s['singleton'] = false;
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
	public static function &get($key, $safe = false)
	{
		if (!isset(self::$objs[$key]))
		{
			if (isset(self::$relations[$key]))
			{
				Finder::useClass( self::$relations[$key]['path'] );
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
				if ($safe)
				{
					return null;
				}
				else
				{
					throw new JSException('Object *'.$key.'* doesn\'t exist in Locator database');
				}
			}
		}

		return self::$objs[$key];
	}

	public static function &getBlock($key, $forceCreate=false)
	{
		$objKey = '__block_'.strtolower($key);
		if (!isset(self::$objs[$objKey]) || $forceCreate)
		{
			// camel case to underscored
			$configName = preg_replace('/([A-Z]+)([A-Z])/','\1_\2', $key);
        	$configName = strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2', $configName));

			$configFile = Finder::findScript('conf', $configName, 0, 1, 'yml', false, 'app');

			if ($configFile)
			{
				if ($controller = Locator::get('controller', true))
				{
					$controllerClass = substr(get_class($controller), 0, -10);
				}
				else
				{
					$controllerClass = '';
				}

				$config = YamlWrapper::load($configFile);

				if (is_array($config))
				{
					if (isset($config[$controllerClass]) && is_array($config[$controllerClass]))
					{
						$config = $config[$controllerClass];
					}
					elseif (isset($config['default']) && is_array($config['default']))
					{
						$config = $config['default'];
					}
				}
				else
				{
					$config = array();
				}

				if ($config['class'])
				{
					$className = $config['class'];
				}
				else
				{
					$className = ucfirst($key).'Block';
				}
			}
			else
			{
				$className = ucfirst($key).'Block';
				$config = array();
			}

			Finder::useClass('blocks/'.$className, 'app');
			Debug::trace('Create block "'.$key.'"', 'locator');

			self::$objs[$objKey] = new $className($config);
		}

		return self::$objs[$objKey];
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
	
	public static function pushStack()
	{
		$length = count(self::$stacks);
		
		self::$stacks[$length] = array(
			'objs' => array(),
			'relations' => array(),
		);
		
		self::$objs = &self::$stacks[$length]['objs'];
		self::$relations = &self::$stacks[$length]['relations'];
	}
	
	public static function popStack()
	{
		$length = count(self::$stacks);
		
		if ($length)
		{
			array_pop(self::$stacks);
			
			$length--;
			if ($length)
			{
				$length--;
				self::$objs = &self::$stacks[$length]['objs'];
				self::$relations = &self::$stacks[$length]['relations'];
			}
			else
			{
				self::pushStack();
			}
		}
	}
}

Locator::pushStack();

?>