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

	protected $rootControllerLoaded = false;

	private function __construct()
	{
		if (Config::exists('routers'))
		{
			$this->routers = Config::get('routers');
		}
		
		if (Config::get('db_disable') && ($key = array_search('Content', $this->routers)))
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

    public static function linkTo($class, $params = null)
    {
		if (strpos($class, '/') !== false)
        {
            $classParams = explode('/', $class, 2);
        }
        else
        {
            $classParams = explode('::', $class, 2);
        }

        if (count($classParams) == 2)
        {
            list($pageClass, $itemClass) = $classParams;
        }
        else
        {
            $pageClass = $class;
            $itemClass = NULL;
        }

        if ($p = self::findByClass($pageClass))
        {
            $url = $p->url_to($itemClass, $params);
        }
        else
        {
            $url = null;
        }

        return $url;
    }
	
	public function addRouter($router)
	{
		if (!in_array($router, $this->routers))
		{
			$this->routers[] = $router;
			$this->routerObjs = null;
		}
	}
	
	public function removeRouter($router)
	{
		$key = array_search($router, $this->routers);
		if ($key !== false)
		{
			unset($this->routers[$key]);
			$this->routerObjs = null;
		}
	}
	
	public function &find($criteria, $routers=NULL)
	{
		$page = NULL;
		$cls = strtolower($criteria['class']);
		$url = $criteria['url'];
		
		if (isset($cls) && array_key_exists($cls, $this->cls2controller))
		{
			if (null === $this->cls2controller[$cls])
			{
				return null;
			}
			else
			{
				return Locator::get($this->cls2controller[$cls]);
			}
		}
		elseif (isset($url) && array_key_exists($url, $this->url2controller))
		{
			if (null === $this->url2controller[$url])
			{
				return null;
			}
			else
			{
				return Locator::get($this->url2controller[$url]);
			}
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
			if ($this->rootControllerLoaded)
			{
				$cls = strtolower(substr(get_class($page), 0, -strlen('Controller')));
				$id = 'controller_'.$cls;
			}
			else
			{
				$id = 'controller';
			}
			
			$this->cls2controller[$cls] = $id;
			$this->url2controller[$page['url']] = $id;
			Locator::bind($id, $page);
		}
		else
		{
			if (isset($url))
			{
				$this->url2controller[$url] = null;
			}
			elseif (isset($cls))
			{
				$this->cls2controller[$cls] = null;
			}
		}

		if (!$this->rootControllerLoaded)
			$this->rootControllerLoaded = true;

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