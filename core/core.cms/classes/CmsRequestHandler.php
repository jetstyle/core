<?php
/**
 Основной обработчик запроса.
 Здесь перегрузим разбор урла.

 */


function __autoload($className)
{
	//старый способ с рекурсивным обходом директорий.
	//	global $app;
	//	$app->UseClass($className, 0, 1, "php", true);
	global $app;
	$dir_name = $app->getPluralizeDir($className);
	if ($app->findDir("classes/" . $dir_name))
	{
		$app->UseClass($dir_name . "/" . $className);
	}
	else
	{
		$app->UseClass($className);
	}
}


class CmsRequestHandler extends RequestHandler
{

	protected function init() 
	{
		$this->base_url .= $this->app_name.'/';
		
		if (!is_array($this->handlers_map))
		{
			$this->handlers_map = array();
		}
		$this->addToHandlersMap("", "HomePage");
		$this->addToHandlersMap("login", "LoginPage");
		$this->addToHandlersMap("start", "StartPage");
		$this->addToHandlersMap("do", "DoPage");
		
		parent::init();
	}
	
	protected function addToHandlersMap($url, $controller)
	{
		if (!isset($this->handlers_map[$url]))
		{
			$this->handlers_map[$url] = $controller;
		}
	}
	
	function initPrincipal()
	{
		// пользователи КМС. для ОЦЕ.
		$this->UseClass($this->pincipal_class);
		$this->principal = &new $this->pincipal_class( $this );
		if ($this->principal->acl_default)
		{
			$this->principal->is_granted_default = false;
			$this->principal->ACL['*'] = array( ROLE_GOD );
			$this->principal->acl_default = false;
		}
		$this->principal->Authorise();
	}

	protected function mapHandler($url)
	{
		$this->useClass("domains/PageDomain");
		$this->pageDomain = new PageDomain($this);
		$this->pageDomain->setDomains(array('Handler'));
		if ($page = & $this->pageDomain->findPageByUrl($url))
		{
			$this->page = & $page;
			$this->data = $page->config;
			$this->params = $page->params;
			$this->path = $page->path;
		} else {
			$this->page = & $this->pageDomain->findPageByClass('_404');
		}
	}
	
	public function deny()
	{
		die($this->tpl->Parse('access_denied.html'));
	}

}

?>