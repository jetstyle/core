<?php

class ModuleConstructor
{
    private $modulePath;
    private $modulePathParts;
    private $moduleName;

    private $handlersType = 'modules';

    private $config;
    private $children;

    public function __construct($modulePath, $config = null) {
        $this->modulePath = $modulePath;
        $this->modulePathParts = explode('/', $modulePath);
        $this->moduleName = $this->modulePathParts[0];

        Finder::prependDir(Config::get('app_dir').$this->handlersType.'/'.$this->moduleName.'/', 'app');

        if (!$config) {
            $ymlFile  = Finder::findScript($this->handlersType, $this->moduleName.'/config', 0, 1, 'yml') ;
            if ( $ymlFile )
            {
                $this->config = YamlWrapper::load($ymlFile);
                $this->markRenderable($this->config);
                $path = array_slice($this->modulePathParts, 1);
                foreach ($path as $pathItem)
                {
                    $this->config = $this->mergeConfigs($this->config, $this->config[$pathItem]);
                }
            }
            else
            {
                throw new JSException('ModuleConstructor: can\'t find config in module '.$this->moduleName);
            }
        }
        else
        {
            $this->config = $config;
        }

        if (is_array($this->config))
        {
            foreach ($this->config as $childName => $childConfig)
            {
                if (is_array($childConfig) && $childConfig['renderable'])
                {
                    unset($this->config[$childName]);
                    $childConfig = $this->mergeConfigs($this->config, $childConfig);
                    $this->children[$childName] = ModuleConstructor::factory($this->modulePath.'/'.$childName, $childConfig);
                }
            }
        }

        $this->config['module_name'] = $this->moduleName;
        $this->config['module_path'] = $this->modulePath;
    }

    private function markRenderable(&$config)
    {
        $result = false;
        if (is_array($config))
        {
            foreach ($config as $key => $child)
            {
                if (is_array($child) && $child['class'])
                {
                    $config[$key]['renderable'] = true;
                    $result = true;
                }
                else
                {
                    if ($this->markRenderable($config[$key]))
                    {
                        $config[$key]['renderable'] = true;
                        $result = true;
                    }
                }
            }
        }
        if ($result) $config['renderable'] = true;
        return $result;
    }

    private function mergeConfigs($config1, $config2)
    {
        foreach ($config1 as $key => $value)
        {
            if ((!is_array($value) || (is_array($value) && !$value['renderable'])) && !$config2[$key])
            {
                $config2[$key] = $config1[$key];
            }
        }
        return $config2;
    }

	public static function factory($modulePath, $config = null)
	{
        $node = new ModuleConstructor($modulePath, $config);
        return $node;
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
            Finder::useClass($this->config['class']);
            $cls = new $this->config['class']($this->config);
            return $cls;
        }
        else
        {
            return false;
        }
    }

    public function getHtml()
    {
        if ($cls = $this->getObj())
        {
            $cls->handle();
            return $cls->getHtml();
        }
        else
        {
            foreach ($this->children as $child)
            {
                $result[] = $child->getHtml();
            }
            $tpl = &Locator::get('tpl');
            $tpl->set('wrapped', $result);
            return $tpl->parse($this->config['template'] ? $this->config['template'] : 'tree_form.html');
        }
    }

    public function getTitle() {
        $sql = "SELECT title FROM ??toolbar WHERE href=".Locator::get('db')->quote( $this->modulePath ) ;
		$current = Locator::get('db')->queryOne($sql);
		return $current['title'];
    }
}
?>
