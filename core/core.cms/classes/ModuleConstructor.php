<?php

Finder::useClass('ModuleConfig');
class ModuleConstructor
{
	public $rh; 				//ссылка на $rh
	public $moduleName = ''; 	//имя текущего модуля

	protected $config;
	protected $handlersType = 'modules';

	public function __construct()
	{
		$this->rh = &RequestHandler::getInstance();
	}

	public function initialize($moduleName)
	{
		//проеряем права
		if( !$this->rh->principal->isGrantedTo('do/'.$moduleName ) )
		{
			return $this->rh->deny();
		}

		//всё ОК
		$this->moduleName = $moduleName;

		// add module dir to DIRS stack
		$module_dir = $this->rh->DIRS[0].$this->handlersType.'/'.$this->moduleName.'/';
		Finder::prependDir($module_dir);

		$this->config = new ModuleConfig($this->rh);
		$this->config->read(Finder::findScript_( $this->handlersType, $this->moduleName.'/defs'));
		$this->config->moduleName = $this->moduleName;
	}

	public function proceed($subModule = '')
	{
		$result = '';

		if ($subModule)
		{
			$result = $this->proceedModule($this->getConfig($subModule));
		}
		else
		{
			$result = $this->proceedModule($this->config);
		}

		return $result;
	}

	public function getTitle()
	{
		return $this->config->module_title;
	}

	protected function proceedModule(&$config)
	{
		// real module
		if ($config->class_name)
		{
			$className = $config->class_name;
			Finder::useClass( $className );
			Debug::trace('ModuleConstructor::InitModule - '.$this->moduleName.'/'.$className );

			$cls = new $className($config);
			$cls->handle();
			return $cls->getHtml();
		}
		// just a wrapper
		elseif (is_array($config->WRAPPED))
		{
			$result = array();
			foreach ($config->WRAPPED AS $subModule)
			{
				$result[] = $this->proceedModule($this->getConfig($subModule, $config));
			}
			$this->rh->tpl->setRef('wrapped', $result);
			return $this->rh->tpl->parse($config->template);
		}
		else
		{
			throw new Exception("ModuleConstructor: error read config for module ".$this->moduleName);
		}
	}

	protected function getConfig($name, $cfg = null)
	{
		//проеряем права
		if( !$this->rh->principal->isGrantedTo('do/'.$this->moduleName.'/'.$what ) )
		{
			return $this->rh->deny();
		}

		if ($cfg)
		{
			$config = clone $cfg;
		}
		else
		{
			$config = clone $this->config;
		}
		$config->read(Finder::findScript_( $this->handlersType, $this->moduleName.'/'.$name));
		return $config;
	}
}
?>