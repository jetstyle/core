<?php
/**
 * Router
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */

class Router
{
	private static $instance = null;
	
	protected $routers = array('Handlers', 'Content');
	protected $routerObjs = null;						// router Obj's
	protected $url2controller = array();				// cache
	protected $cls2controller = array();				// cache

	private function __construct()
	{
		if (Config::get('routers'))
		{
			$this->routers = Config::get('routers');
		}
		
		if (!Config::get('db_disable') && ($key = array_search('Handlers', $this->routers)))
		{
			unset($this->routers[$key]);
		}
	}
	
	public static function &getInstance()
	{
		if (null === self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function &findByUrl($url)
	{
		return self::getInstance()->find(array('url' => $url));
	}

	public static function &findByClass($class)
	{
		return self::getInstance()->find(array('class' => $class));
	}

	public function &find($criteria, $routers=NULL)
	{
		$page = NULL;
		$cls = strtolower($criteria['class']);
		$url = $criteria['url'];
		
		if (isset($url) && isset($this->url2controller[$url]))
		{
			return Locator::get($this->url2controller[$url]);
		}

		if (isset($cls) && isset($this->cls2controller[$cls]))
		{
			return Locator::get($this->cls2controller[$cls]);
		}

		if (!isset($routers))
		{
			$routers = &$this->getRouters();
		}
			
		foreach ($routers AS $router)
		{
			if ($page = &$router->find($criteria))
			{
				break;
			}
		}

		if (null !== $page)
		{
			$cls = strtolower(substr(get_class($page), 0, -strlen('Controller')));
			if (Locator::exists('controller'))
			{
				$id = $cls;
			}
			else
			{
				$id = 'controller';
			}
			$this->cls2controller[$cls] = $id;
			$this->url2controller[$page['url']] = $id;
			Locator::bind($id, $page);
		}

		return $page;
	}

	protected function &getRouters()
	{
		if ( null === $this->routerObjs)
		{
			$this->routerObjs = array();
			
			Finder::useClass('routers/BasicRouter');
			foreach($this->routers AS $router)
			{
				$className = ucfirst($router).'Router';
				Finder::useClass('routers/'.$className);
				$this->routerObjs[$router] = new $className();
			}
		}
		return $this->routerObjs;
	}

}

?>