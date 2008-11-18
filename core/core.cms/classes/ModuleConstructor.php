<?php

Finder::useClass('ModuleConfig');
class ModuleConstructor
{
	public $moduleName = ''; 	//имя текущего модуля

	protected $config;
	protected $handlersType = 'modules';
	protected $params = array();
	protected $path = array();
	
	public function initialize($moduleName, $params = null)
	{
		//проеряем права
		if( !Locator::get('principal')->security('cmsModules', $moduleName.($params ? '/'.implode('/', $params) : '') ) )
		{
			return Controller::deny();
		}

		//всё ОК
		$this->moduleName = $moduleName;

		// add module dir to DIRS stack
		Finder::prependDir(Config::get('app_dir').$this->handlersType.'/'.$this->moduleName.'/');

		$this->config = new ModuleConfig();
		$defsPath = Finder::findScript( $this->handlersType, $this->moduleName.'/defs');
		if (!$defsPath)
		{
			Controller::_404();
		}
		$this->config->read($defsPath);
		$this->config->moduleName = $this->moduleName;
		
		$this->path[] = $this->moduleName;
		
		if (is_array($params))
		{
			$this->params = $params;
		}
	}

	public function proceed()
	{
		return $this->proceedModule($this->config);
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

			$config->componentPath = implode('/', $this->path);
			
			$cls = new $className($config);
			$cls->handle();
			return $cls->getHtml();
		}
		// just a wrapper
		elseif (is_array($config->WRAPPED))
		{
			if (count($this->params) > 0)
			{
				$neededSubModule = array_shift($this->params);
			}
			else
			{
				$neededSubModule = null;
			}
			
			$result = array();
			foreach ($config->WRAPPED AS $subModule)
			{
				$_subModule = array_pop(explode('/', $subModule));
				if ($neededSubModule && $neededSubModule != $_subModule)
				{
					continue;
				}
				$this->path[] = $_subModule;
				$result[] = $this->proceedModule($this->getConfig($subModule, $config));
				array_pop($this->path);
			}
			
			if ($neededSubModule)
			{
				if (empty($result))
				{
					$this->path[] = $neededSubModule;
					$result = $this->proceedModule($this->getConfig($neededSubModule, $config));
					array_pop($this->path);
					return $result;
				}
				else
				{
					return $result[0];
				}
			}
			else
			{
				$tpl = &Locator::get('tpl');
				$tpl->setRef('wrapped', $result);
				return $tpl->parse($config->template);
			}
		}
		else
		{
			throw new Exception("ModuleConstructor: error read config for module ".$this->moduleName);
		}
	}

	protected function getConfig($name, $cfg = null)
	{
		//проеряем права
//		if( !Locator::get('principal')->security('cmsModules', $this->moduleName.'/'.$name ) )
//		{
//			return Controller::deny();
//		}

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