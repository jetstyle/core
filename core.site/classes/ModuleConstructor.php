<?php

class ModuleConstructor
{
	private $modulePath;
	private $modulePathParts;
	private $moduleName;

	private $handlersType = 'modules';

	private $config = array();
	private $children;

	private $childrenNumbered;
	private $currentChild = 0;

	public static function factory($modulePath, $config = null)
	{
		$node = new ModuleConstructor($modulePath, $config);
		return $node;
	}

	public function __construct($modulePath, $config = null)
	{
		$modulePath = trim($modulePath, '/');
		$this->modulePath = $modulePath;
		$this->modulePathParts = explode('/', $modulePath);
		$this->moduleName = $this->modulePathParts[0];

		Finder::pushContext();
		Finder::prependDir(Config::get('cms_dir').$this->handlersType.'/'.$this->moduleName.'/');
		$ymlFile  = Finder::findScript_($this->handlersType, $this->moduleName.'/config', 0, 1, 'yml') ;
		Finder::popContext();

		$this->config = YamlWrapper::load($ymlFile);

		if (!is_array($this->config))
		{
			$this->config = array();
		}

		$this->markRenderable($this->config);

		for ($i = 1; $i < count($this->modulePathParts); $i++)
		{
			$this->config = $this->mergeConfigs($this->config, $this->config[$this->modulePathParts[$i]]);
		}

		if (is_array($config) && !empty($config))
		{
			$this->config = $this->mergeConfigs($this->config, $config);
		}

		foreach ($this->config AS $childName => $childConfig)
		{
			if (is_array($childConfig) && $childConfig['renderable'])
			{
				unset($this->config[$childName]);
				//$childConfig = $this->mergeConfigs($this->config, $childConfig);
				$this->children[$childName] = ModuleConstructor::factory($this->modulePath.'/'.$childName);
			}
			/*
			elseif (is_string($childConfig) && $childConfig[0] == '@')
			{
				unset($this->config[$childName]);
				$this->children[$childName] = ModuleConstructor::factory(substr($childConfig, 1));
			}
			*/
		}

		$this->config['module_name'] = $this->moduleName;
		$this->config['module_path'] = $this->modulePath;
	}

	private function markRenderable(&$config)
	{
		$isRenderable = false;

		if (is_array($config))
		{
			if ($config['class'])
			{
				$isRenderable = true;
			}

			foreach ($config AS &$child)
			{
				if ($this->markRenderable($child))
				{
					$isRenderable = true;
				}
			}
		}

		if ($isRenderable)
		{
			$config['renderable'] = true;
		}

		return $isRenderable;
	}

	private function mergeConfigs($config1, $config2)
	{
		foreach ($config1 AS $key => $value)
		{
			if ((!is_array($value) || (is_array($value) && !$value['renderable'])) && !$config2[$key])
			{
				$config2[$key] = $config1[$key];
			}
		}
		return $config2;
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function getChildren()
	{
		return $this->children;
	}

	public function get($name)
	{
		if ($this->children[$name])
		{
			return $this->children[$name];
		}
		else
		{
			return false;
		}
	}

	public function getList()
	{
		return $this->get('list');
	}

	public function getForm()
	{
		return $this->get('form');
	}

	public function getObj()
	{
		if ($this->config['class'])
		{
			Finder::pushContext();
			Finder::prependDir(Config::get('cms_dir').$this->handlersType.'/'.$this->moduleName.'/');

			Finder::useClass($this->config['class']);
			$cls = new $this->config['class']($this->config);
			Finder::popContext();
			return $cls;
		}
		else
		{
			return false;
		}
	}

	public function getHtml()
	{
		$result = '';

		Finder::pushContext();
		Finder::prependDir(Config::get('cms_dir').$this->handlersType.'/'.$this->moduleName.'/');

		if ($cls = $this->getObj())
		{
			$cls->handle();
			$result = $cls->getHtml();
		}
		else
		{
			$tpl = &Locator::get('tpl');

			if (is_array($this->children))
			{
				$result = array();

				while ($nextChild = $this->getNextChild())
				{
					$result[] = $nextChild->getHtml();
				}

				$tpl->set('wrapped', $result);
			}
			$result = $tpl->parse($this->config['template'] ? $this->config['template'] : 'tree_form.html');
		}

		Finder::popContext();

		return $result;
	}

	public function getTitle()
	{
		$sql = "SELECT title FROM ??toolbar WHERE href=".Locator::get('db')->quote( $this->modulePath ) ;
		$current = Locator::get('db')->queryOne($sql);
		return $current['title'];
	}

	public function replaceForm($formPath)
	{
		$this->children['form'] = ModuleConstructor::factory($formPath);
	}

	private function getNextChild()
	{
		if (!$this->childrenNumbered)
		{
			$this->childrenNumbered = array_keys($this->children);
		}
		$currentChild = $this->currentChild;
		$this->currentChild++;
		return $this->children[$this->childrenNumbered[$currentChild]];
	}

}
?>
