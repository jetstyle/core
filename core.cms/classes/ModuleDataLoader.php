<?php

/*
 * Implements API for external interaction
 *
 */

Finder::useClass('ModuleConstructor');
class ModuleDataLoader {

	protected $moduleName = null;
	private $handlersType = 'modules';

	private $config = null;

	protected $listPath = 'list';
	protected $formPath = 'form`';

	public function __construct($moduleName = null)
	{
		if ($moduleName)
		{
        	$this->moduleName = $moduleName;
		}
	}

	public function getData($parent)
	{
        $list = $this->getObject($this->listPath);
        $items = $chidlren = array();
        Finder::useClass('Inflector');
       	$inflector = new Inflector();
       	$controller = $inflector->underscore($this->moduleName);
		$parentRow = DBModel::factory('Content')->loadOne('{id} = '.intval($parent));
        foreach ($list->getAllItems() as $item)
        {
        	$item['id'] = $controller.'-'.$item['id'];
			$item['_level'] = $parentRow['_level']+1;
			$item['custom_buttons'] = true;
			$item['hide_buttons']['addChild'] = true;
			$item['form_config'] = $this->moduleName.'/'.$this->formPath;
         	$items[$item['id']] = $item;
        	$children[$parent][] = $item['id'];
        }
        return array('items' => $items, 'children' => $children);
	}

	public function delete($item)
	{
    	$itemParts = explode('-', $item);
    	$itemForm = $this->getObject($this->formPath);
    	$itemForm->setId($itemParts[count($itemParts)-1]);
    	$itemForm->load();
    	$itemForm->delete();
	}

	public function insert($parent)
	{

	}

	public function updateTitle($item, $title)
	{
		$itemParts = explode('-', $item);
		$typo = &Locator::get('typografica');
		$titlePre = $typo->correct($title, true);

		$itemForm = $this->getObject($this->formPath);

		$db = &Locator::get('db');
		$db->execute("
			UPDATE ??".$itemForm->getTableName()."
			SET title = ".$db->quote($title).", title_pre = ".$db->quote($titlePre)."
			WHERE id = ".intval($itemParts[count($itemParts)-1])."
		");
	}

	public function getParentsForItem($itemId)
	{
		return array();
	}

	protected function getObject($path)
	{
		return ModuleConstructor::factory($this->moduleName.'/'.$path)->getObj();
	}

}

?>
