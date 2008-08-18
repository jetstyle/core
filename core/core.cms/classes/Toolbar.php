<?php
/**
 * @author lunatic lunatic@jetstyle.ru
 *
 * @modified 09.05.2008
 */

 class Toolbar
 {
 	protected $items = array('main' => array(), 'submenu' => array());
 	protected $goToList;
	protected $db = null;
	protected $tpl = null;

	protected $possiblePaths = null;
	
 	public function __construct()
 	{
 		$this->db = &Locator::get('db');
 		$this->tpl = &Locator::get('tpl');
 	}

 	public function getData()
 	{
 		$this->load();
 		return $this->items;
 	}

 	public function getGoToList() {
    	$this->loadGoTo();
    	return $this->goToList;
 	}

 	/**
 	 * load two levels of menu
 	 */
 	protected function load()
 	{
 		$this->constructResult($this->getLoadResult());
 	}

 	protected function loadGoTo() {
    	$this->goToList = $this->db->query("" .
 			"SELECT title_pre AS title, _path AS path " .
 			"FROM ??content " .
 			"WHERE controller != '' " .
 			"ORDER BY _level,_order " .
 		"");
 	}

 	protected function getLoadResult()
 	{
 		return $this->db->execute("" .
 				"SELECT id, title, href, _level, _parent " .
 				"FROM ??toolbar " .
 				"WHERE _state = 0 AND _level IN (1,2) " .
 				"ORDER BY _level ASC, _order ASC " .
 		"");
 	}

 	protected function constructResult($result)
 	{
		$paths = $this->getPossiblePaths(Locator::get('controller')->getParams());
		
		$principal = &Locator::get('principal');

 		while($r = $this->db->getRow($result))
 		{
 			$r['granted'] = $principal->isGrantedTo($r['href']);


 			if($r['_level'] == 1)
 			{
 				$this->items['main'][$r['id']] = $r;
 			}
 			else
 			{
 				if(!$r['granted'])
 				{
 					continue;
 				}
 				elseif($r['granted'] && $this->items['main'][$r['_parent']])
 				{
 					$this->items['main'][$r['_parent']]['granted'] = true;
 				}

 				if(!isset($this->items['submenu'][$r['_parent']]))
 				{
 					$this->items['submenu'][$r['_parent']] = array('id' => $r['_parent'], 'childs' => array());
 				}
 				$this->items['submenu'][$r['_parent']]['childs'][$r['id']] = $r;
 			}
 			if($r['href'] && in_array($r['href'], $paths))
 			{
 				if($this->items['main'][$r['id']])
 				{
 					$this->items['main'][$r['id']]['selected'] = true;
 					$this->tpl->set('menu_selected', $r['id']);
 				}
 				else
 				{
 					$this->items['submenu'][$r['_parent']]['childs'][$r['id']]['selected'] = true;
 					$this->items['submenu'][$r['_parent']]['selected'] = true;
 					$this->items['main'][$r['_parent']]['selected'] = true;
 					$this->tpl->set('menu_selected', $r['_parent']);
 				}
 			}
 		}

 		foreach($this->items['main'] AS $k => $item)
 		{
 			if(!$item['granted'])
 			{
 				unset($this->items['submenu'][$item['id']], $this->items['main'][$k]);
 			}
 		}
 	}
 	
 	protected function getPossiblePaths($urlParts)
	{
		if ( null === $this->possiblePaths)
		{
			$this->possiblePaths = array();
			do
			{
				$this->possiblePaths[] = implode ("/", $urlParts);
			}
			while (array_pop($urlParts) && $urlParts);
		}
		return $this->possiblePaths;
	}

 }
?>