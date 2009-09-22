<?php
/**
 * ListFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('Inflector');

abstract class ListFilter
{
    protected $template;

    private $listObj = null;
    private $config;
    private $model;
    
    static public function factory($config, $listObj = null)
    {
        $className = 'List'.Inflector::camelize($config['type']).'Filter';
        Finder::useClass($className);
        $filterObj = new $className($config, $listObj);

        if (!in_array('ListFilter', class_parents($filterObj)))
        {
            throw new JSException("Class \"".get_class($filterObj)."\" must extends from ListFilter");
        }

        return $filterObj;
    }

    public function __construct($config, $listObj = null)
    {
        $this->config = $config;
        if ($listObj)
        {
            $this->listObj = $listObj;
        }
        $this->checkConfig();

        $template = $this->getConfig('template');
        if ($template)
        {
            $this->template = $template;
        }

        $this->init();
    }

    public function getHtml()
    {
        $this->loadData();

        $tpl = Locator::get('tpl');
        $tpl->setRef('*', $this->getTplData());
        return $tpl->parse($this->template);
    }

    public function getByKey($key)
    {

    }

    abstract public function getValue();
    abstract public function apply(&$model);

    abstract protected function init();
    abstract protected function getTplData();
    abstract protected function constructModel();

    protected function getModel()
    {
        if (null === $this->model)
        {
            $this->model = $this->constructModel();
            $this->applyDependencies($this->model);
        }

        return $this->model;
    }

    protected function applyDependencies(&$model)
    {
        $depends = $this->getConfig('depends');
        if ($depends)
        {
            if (!is_array($depends))
            {
                $depends = array($depends);
            }

            foreach ($depends AS $filterKey)
            {
                $filter = $this->getListObj()->getFiltersObject($filterKey);
                $filter->apply($model);
            }
        }
    }

    protected function disableFilter(&$model)
    {
        $model = null;
    }

    protected function loadData()
    {
        $model = $this->getModel();
        if ($model)
        {
            $model->load();
        }
    }

    protected function getConfig($key = null)
    {
        if (null !== $key )
        {
            return $this->config[$key];
        }
        else
        {
            return $this->config;
        }
    }

    protected function getListObj()
    {
        return $this->listObj;
    }

    protected function checkConfig()
    {
        if (is_array($this->neededConfigVars) && !empty($this->neededConfigVars))
        {
            if (!is_array($this->config))
            {
                throw new JSException("You must define \"".implode('", "', $this->neededConfigVars)."\" in config (".get_class($this).")");
            }
            
            foreach ($this->neededConfigVars AS $key)
            {
                if (!array_key_exists($key, $this->config))
                {
                    throw new JSException("You must define \"".$key."\" in config (".get_class($this).")");
                }
            }
        }
    }
}
?>