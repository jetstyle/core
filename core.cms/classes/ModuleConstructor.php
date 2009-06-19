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
        if( !Locator::get('principal')->security('cmsModules', $modulePath) )
		{
			return Controller::deny();
		}

        $this->modulePath = $modulePath;
        $this->modulePathParts = explode('/', $modulePath);
        $this->moduleName = $this->modulePathParts[0];

        Finder::prependDir(Config::get('app_dir').$this->handlersType.'/'.$this->moduleName.'/', 'app');

        if (!$config) {
            $ymlFile  = Finder::findScript($this->handlersType, $this->moduleName.'/config', 0, 1, 'yml') ;
            if ( $ymlFile )
            {
                $this->config = YamlWrapper::load($ymlFile);
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
            foreach ($this->config as $key => $child)
            {
                if (is_array($child) && $child['class'])
                {
                    foreach ($this->config as $key => $value)
                    {
                        if (!(is_array($value) && $value['class']) && !$child[$key])
                        {
                            $child[$key] = $this->config[$key];
                        }
                    }
                    $this->children[$key] = ModuleConstructor::factory($this->modulePath.'/'.$key, $child);
                    unset($this->config[$key]);
                }
            }
        }
        
        $this->config['module_name'] = $this->moduleName;
        $this->config['module_path'] = $this->modulePath;
    }

	public static function factory($modulePath, $config = null)
	{
        $node = new ModuleConstructor($modulePath, $config);
        return $node;
	}

    public function getHtml()
    {
        if ($this->config['class'])
        {
            Finder::useClass($this->config['class']);
            $cls = new $this->config['class']($this->config);
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
