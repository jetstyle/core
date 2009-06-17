<?php

class ModuleConstructor
{
    private $modulePath;
    private $modulePathParts;
    private $moduleName;

    private $handlersType = 'modules';

    private $config;
    private $children;

    public function __construct($modulePath) {
        if( !Locator::get('principal')->security('cmsModules', $modulePath) )
		{
			return Controller::deny();
		}

        $this->modulePath = $modulePath;
        $this->modulePathParts = explode('/', $modulePath);
        $this->moduleName = $this->modulePathParts[0];

        Finder::prependDir(Config::get('app_dir').$this->handlersType.'/'.$this->moduleName.'/', 'app');

        $ymlFile  = Finder::findScript($this->handlersType, $this->moduleName.'/config', 0, 1, 'yml') ;
		if ( $ymlFile )
		{
			$this->config = YamlWrapper::load($ymlFile);
            $this->config['module_name'] = $this->moduleName;
            $this->config['module_path'] = $this->modulePath;

            $pathParts = $this->modulePathParts;
            array_shift($pathParts);
            foreach ($pathParts as $part)
            {
                foreach ($this->config as $key => $value)
                {
                    if (!(is_array($value) && $value['class']) && !$this->config[$part][$key])
                    {
                        $this->config[$part][$key] = $this->config[$key];
                    }
                }
                $this->config = $this->config[$part];
            }
            if (is_array($this->config))
            {
                foreach ($this->config as $key => $child)
                {
                    if ($this->config['class'])
                    {

                    }
                    elseif (is_array($child) && $child['class'])
                    {
                        $this->children[$key] = ModuleConstructor::factory($this->modulePath.'/'.$key);
                        unset($this->config[$key]);
                    }
                }
            }
        }
        else
        {
            throw new JSException('ModuleConstructor: can\'t find config in module '.$this->moduleName);
        }
    }

	public static function factory($modulePath)
	{
        $node = new ModuleConstructor($modulePath);
        return $node;
	}

    public function getHtml()
    {
        if ($this->config['class'])
        {
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
