<?php
/**
 * Handlers router
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */
class HandlersRouter extends BasicRouter
{
	private $handlersMap = array();

	public function __construct()
	{
		$this->handlersMap = Config::get('handlers_map');
		if (!is_array($this->handlersMap))
		{
			$this->handlersMap = array();
		}
	}

	public function &find($criteria)
	{
		if (empty($criteria)) return null;

		if (isset($criteria['url'])) return $this->findByUrl($criteria['url']);
		if (isset($criteria['class'])) return $this->findByClass($criteria['class']);
		return null;
	}

	private function findByUrl($url)
	{
		$possiblePaths = $this->getPossiblePaths($url);

		foreach ($possiblePaths AS $up)
		{
			if (isset($this->handlersMap[$up]) || isset($this->handlersMap[$up."/"]))
			{
				//если найдено точно соответсвие
				if (isset($this->handlersMap[$up]))
				{
					$handler = $this->handlersMap[$up];
				}
				//если мап многие ко многим
				// else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				// {
				// 	$_handler = $url;
				// }
				//каталог мапится на один хендлер (многие к одному)
				elseif ($this->handlersMap[$up."/"])
				{
					$handler = $this->handlersMap[$up."/"];
				}

				/*
				* Проверка наличия контроллера на диске
				*/
				if (!empty($handler))
				{
					$config = array (
						'class' => $handler,
						'path' => $up,
						'url' => $url,
					);
					if (Finder::findScript("classes/controllers", $handler))
					{
						Finder::useClass("controllers/".$handler);
						return $this->buildController($config);
					}
				}
			}
		}
		return null;
	}

	function findByClass($class)
	{
		/*
		* Проверка наличия контроллера на диске
		*/
		if (!empty($class))
		{
			if (substr($class, -10) != 'Controller')
			{
				$class .= 'Controller';
			}

			if ($path = array_search($class, $this->handlersMap))
			{
				$config = array (
					'class' => $class,
					'path' => $path,
					'url' => $path
				);
				
				if (Finder::findScript("classes/controllers", $class))
				{
					Finder::useClass("controllers/".$class);
					return $this->buildController($config);
				}
			}
		}
		return null;
	}
}
?>