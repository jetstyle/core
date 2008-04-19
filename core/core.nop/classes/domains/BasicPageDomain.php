<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 */
 
class BasicPageDomain
{
	var $possible_paths = NULL;
	var $handler = NULL;
	var $path = NULL;
	var $url = NULL;
	var $config = array();

	function __construct()
	{
	}

	function initialize(&$ctx, $config=NULL)
	{
		$this->rh =& $ctx;
		if (isset($config)) $this->config = array_merge($this->config, $config);
	}

	function &find($criteria=NULL) { return False; }

	function getPossiblePaths($url)
	{
		if (!isset($this->possible_paths))
		{
			$this->possible_paths =& $this->buildPossiblePaths($url);
		}
		return $this->possible_paths;
	}

	function &buildPossiblePaths($url)
	{
		return $this->buildMaxPaths($url);
	}

	function buildMaxPaths($url)
	{
		$url_parts = explode("/", rtrim($url, "/"));
		$max_path = array();
		do
			$max_path[] = implode ("/", $url_parts);
		while (array_pop($url_parts) && $url_parts);
		return $max_path;
	}

	function getParams($url, $path)
	{
		return explode("/", trim(substr($url, strlen($path)+1)) );
	}

	function &buildPage($config)
	{
		$page = NULL;

		$page_cls = $config['class'];
		if (class_exists($page_cls))
		{
			$page =& new $page_cls();
			$page->domain =& $this;
			$page->url = $config['url'];
			$page->path = $config['path'];
			$page->params = $this->getParams($page->url, $page->path);
			$this->rh->_onCreatePage($page,$config);
			$page->initialize($this->rh, $config['config']);
		}

		return $page;
	}
}
?>