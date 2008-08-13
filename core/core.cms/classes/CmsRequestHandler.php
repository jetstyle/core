<?php
class CmsRequestHandler extends RequestHandler
{
	public function init()
	{
		$this->addToHandlersMap("", "HomeController");
		$this->addToHandlersMap("login", "LoginController");
		$this->addToHandlersMap("start", "StartController");
		$this->addToHandlersMap("do", "DoController");
		$this->addToHandlersMap("pack_modules", "ModulePackerController");

		parent::init();
		
		$this->initEnvironment();
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
		$this->tpl->set('fe_/', Config::get('front_end_path'));
	}

	public function deny()
	{
		die($this->tpl->Parse('access_denied.html'));
	}

}

?>