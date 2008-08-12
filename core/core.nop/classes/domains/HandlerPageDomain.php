<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 *
 * Страницы среди классов страниц
 */

class HandlerPageDomain extends BasicPageDomain
{
	private $handlers_map = array();

	function findByUrl($url)
	{
		$possible_paths = $this->getPossiblePaths($url);

		$this->handlers_map = $this->rh->handlers_map;

		foreach ($possible_paths as $up)
		{
			if (isset($this->handlers_map[$up]) || isset($this->handlers_map[$up."/"]))
			{
				//если найдено точно соответсвие
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//если мап многие ко многим
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//каталог мапится на один хендлер (многие к одному)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				* Проверка наличия контроллера на диске
				*/
				if (!empty($_handler))
				{
					$page_cls = $_handler;
					$config = array (
						'class' => $page_cls,
						'config' => array (),
						'path' => $up,
						'url' => $url,
					);
					if (Finder::findScript("classes/controllers", $page_cls))
					{
						Finder::useClass("controllers/".$page_cls);
						if ($this->handler = &$this->buildPage($config))
						{
							return True;
						}
					}
				}
			}
		}
		return False;
	}

	function findByClass($page_cls)
	{
		/*
		* Проверка наличия контроллера на диске
		*/
		if (!empty($page_cls))
		{
			if (substr($page_cls, -4) != 'Page')
			{
				$page_cls .= 'Page';
			}

			if (($page_cls == 'PageNotFoundPage') || ($path = array_search($page_cls, $this->rh->handlers_map)))
			{
				$config = array (
					'class' => $page_cls,
					'config' => array (),
					'path' => $path
				);

				$config['url'] = $config['path'];
				if (Finder::findScript("classes/controllers", $page_cls))
				{
					Finder::useClass("controllers/".$page_cls);
					if ($this->handler = &$this->buildPage($config))
					{
						return True;
					}
				}
			}
		}
		return False;
	}

	function &find($criteria)
	{
		if (empty($criteria)) return False;

		if (isset($criteria['url'])) return $this->findByUrl($criteria['url']);
		if (isset($criteria['class'])) return $this->findByClass($criteria['class']);
		return False;
	}

}

?>