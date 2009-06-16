<?php

class ModuleConstructor
{
    private $modulePath;
    private $modulePathParts;
    private $moduleName;

    private $handlersType = 'modules';

    private $config;
    private $children;

	public static function factory($modulePath)
	{
		if( !Locator::get('principal')->security('cmsModules', $modulePath) )
		{
			return Controller::deny();
		}

        $node = new ModuleConstructor();

		$node->modulePath = $modulePath;
        $node->modulePathParts = explode('/', $modulePath);
        $node->moduleName = $node->modulePathParts[0];

        Finder::prependDir(Config::get('app_dir').$node->handlersType.'/'.$node->moduleName.'/', 'app');

        $ymlFile  = Finder::findScript($node->handlersType, $node->moduleName.'/config', 0, 1, 'yml') ;
		if ( $ymlFile )
		{
			$node->config = YamlWrapper::load($ymlFile);
            $pathParts = $node->modulePathParts;
            array_shift($pathParts);
            foreach ($pathParts as $part)
                $node->config = $node->config[$part];
            if (is_array($node->config))
            {
                foreach ($node->config as $key => $child)
                {
                    if ($node->config['class'])
                    {

                    }
                    elseif (is_array($child) && $child['class'])
                    {
                        $node->children[$key] = ModuleConstructor::factory($node->modulePath.'/'.$key);
                        unset($node->config[$key]);
                    }
                }
            }
        }
        else
        {
            throw new JSException('ModuleConstructor: can\'t find config in module '.$node->moduleName);
        }
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
            return $tpl->parse($this->config['template']);
        }
    }

    public function getTitle() {
        return 'mock title';
    }
}
?>
