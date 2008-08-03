<?php
/**
 Основной обработчик запроса.
 Здесь перегрузим разбор урла.

 */

class CmsRequestHandler extends RequestHandler
{

	public function __construct($config_path = 'config/default.php')
	{
		parent::__construct($config_path);
		$this->front_end->project_title = $this->project_title;
		$this->project_title = 'CMS: '.$this->project_title;
	}
	
	public function init() 
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
		$this->addToHandlersMap("pack_modules", "ModulePackerPage");
		
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
		$this->useClass($this->pincipal_class);
		$this->principal = &new $this->pincipal_class( $this );
		if ($this->principal->acl_default)
		{
			$this->principal->is_granted_default = false;
			$this->principal->ACL['*'] = array( ROLE_GOD );
			$this->principal->acl_default = false;
		}
		$this->principal->authorise();
	}

	protected function initEnvironment()
	{
		$this->tpl->set('user', $this->principal->getUserData());
		
		$this->tpl->set('fe_/', $this->front_end->path_rel);
		
		parent::initEnvironment();
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
		} 
		else 
		{
			$this->_404();
		}
	}
	
	public function deny()
	{
		die($this->tpl->Parse('access_denied.html'));
	}

}

?>