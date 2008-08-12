<?php
/**
 Основной обработчик запроса.
 Здесь перегрузим разбор урла.

 */

class CmsRequestHandler extends RequestHandler
{

	public function __construct()
	{
		parent::__construct();
		$this->project_title = 'CMS: '.Config::get('project_title');
	}

	public function init()
	{
		Config::set('base_url', Config::get('base_url').Config::get('app_name').'/');
		Config::set('rootHref', Config::get('base_url').'skins/');

		$this->addToHandlersMap("", "HomeController");
		$this->addToHandlersMap("login", "LoginController");
		$this->addToHandlersMap("start", "StartController");
		$this->addToHandlersMap("do", "DoController");
		$this->addToHandlersMap("pack_modules", "ModulePackerController");

		parent::init();
	}

	protected function addToHandlersMap($url, $controller)
	{
		$hm = Config::get('handlers_map');
		if (!is_array($hm))
		{
			$hm = array();
		}
		if (!isset($hm[$url]))
		{
			$hm[$url] = $controller;
			Config::set('handlers_map', $hm);
		}
	}

	function initPrincipal()
	{
		$class = Config::get('pincipal_class');
		Finder::useClass($class);
		$this->principal = &new $class();
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

	public function deny()
	{
		die($this->tpl->Parse('access_denied.html'));
	}

}

?>