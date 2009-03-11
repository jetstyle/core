<?php

/*
 * Implements API for external interaction
 *
 */

Finder::useClass('ModuleConfig');
class ModuleDataLoader {
	protected $moduleName = null;
	private $handlersType = 'modules';

	private $config = null;
	protected $listPath = 'list.php';
	protected $formPath = 'form.php';

	public function __construct($moduleName = null)
	{
		if ($moduleName)
		{        	$this->moduleName = $moduleName;
		}	    /*else
	    {			$moduleDir = dirname(dirname(__FILE__));
			$matches = array();
			preg_match('|[\w\d]*$|', $moduleDir, $matches);
			$this->moduleName = $matches[0];
	    }*/
	}

	public function getData($parent)
	{
        $list = $this->getObject($this->listPath);
        $items = $chidlren = array();
        Finder::useClass('Inflector');
       	$inflector = new Inflector();
       	$controller = $inflector->underscore($this->moduleName);
        foreach ($list->getAllItems() as $item)
        {
        	$item['id'] = $controller.'-'.$item['id'];        	$items[$item['id']] = $item;
        	$children[$parent][] = $item['id'];
        }
        return array('items' => $items, 'children' => $children);
	}

	public function delete($item)
	{    	$itemParts = explode('-', $item);
    	$itemForm = $this->getObject($this->formPath);
    	$itemForm->setId($itemParts[count($itemParts)-1]);
    	$itemForm->load();
    	$itemForm->delete();
	}

	protected function getObject($configPath)
	{    	$config = new ModuleConfig();
		$listPath = Config::get('app_dir').$this->handlersType.'/'.$this->moduleName.'/'.$configPath;
		$config->read($listPath);
		$config->moduleName = $this->moduleName;
		$className = $config->class_name;
		return new $className($config);
	}

}

?>