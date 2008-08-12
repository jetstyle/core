<?php
/**
 * Basic router
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 */
 
abstract class BasicRouter
{
	protected $possiblePaths = null;

	// var $handler = null;
	// var $path = null;
	// var $url = null;
	// var $config = array();

	abstract function &find($criteria);

	protected function getPossiblePaths($url)
	{
		if ( null === $this->possiblePaths)
		{
			$urlParts = explode("/", rtrim($url, "/"));
			$this->possiblePaths = array();
			do
			{
				$this->possiblePaths[] = implode ("/", $urlParts);
			}
			while (array_pop($urlParts) && $urlParts);
		}
		return $this->possiblePaths;
	}

	protected function getParams($url, $path)
	{
		return explode("/", trim(substr($url, strlen($path)+1)) );
	}

	protected function &buildController($config)
	{
		$page = NULL;

		if (class_exists($config['class']))
		{
			$page =& new $config['class']();
			$page->setUrl($config['url']);
			$page->setPath($config['path']);
			$page->setParams($this->getParams($config['url'], $config['path']));
			
			if ($config['data'])
			{
				$page->setData($config['data']);
			}
			
			RequestHandler::getInstance()->_onCreatePage($page,$config);
		}

		return $page;
	}
}
?>