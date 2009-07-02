<?php
/**
 * ListFilter
 *
 * @author lunatic
 */
abstract class ListFilter
{
    static public $varCount = 0;

    protected $template;

    private $listObj = null;
    private $config;

    public function __construct($config, $listObj)
    {
        $this->config = $config;
        $this->listObj = $listObj;
        $this->checkConfig();

        $template = $this->getConfig('template');
        if ($template)
        {
            $this->template = $template;
        }

        $this->init();
    }

    abstract public function getHtml();
    abstract public function getValue();
    abstract public function apply($model);

    abstract protected function init();

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

    protected function getUniqueGetVar()
    {
        return $this->getListObj()->getPrefix().'_'.self::$varCount++;
    }
}
?>