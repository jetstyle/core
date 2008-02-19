<?php
/*
 * Created on 30.11.2007
 *
 */
 
 class Toolbar
 {
 	protected $rh;
 	protected $items = array();		// first level
 	protected $subItems = array();	// second level
 	
 	public function __construct(&$rh)
 	{
 		$this->rh = &$rh;
 	}
 	
 	public function handle()
 	{
 		if(!$this->rh->getVar('hide_toolbar'))
 		{
 			$this->load();
 			$this->rh->tpl->set('menu', $this->items);
 			$this->rh->tpl->set('menu_submenu', $this->subItems);
 			$this->rh->tpl->set('show_toolbar', true);
 		}
 		else
 		{
 			$this->rh->state->Set('hide_toolbar',1);
 			$this->rh->tpl->set('show_toolbar', false);
 		}
 	}
 	
 	/**
 	 * load two levels of menu
 	 */
 	protected function load()
 	{
 		if($result = $this->rh->db->execute("" .
 				"SELECT id, title, href, _level, _parent " .
 				"FROM ??toolbar " .
 				"WHERE _state = 0 AND _level IN (1,2) " .
 				"ORDER BY _level ASC, _order ASC " .
 		""))
 		{
	 		$module_name = 'do/'.$this->rh->getVar('module');
	 		while($r = $this->rh->db->getRow($result))
	 		{
	 			if($r['_level'] == 1)
	 			{
	 				$this->items[$r['id']] = $r;
	 			}
	 			else
	 			{
	 				if(!isset($this->subItems[$r['_parent']]))
	 				{
	 					$this->subItems[$r['_parent']] = array('id' => $r['_parent'], 'childs' => array());
	 				}
	 				$this->subItems[$r['_parent']]['childs'][$r['id']] = $r;
	 			}
	 			if($module_name == $r['href'])
	 			{
	 				if($this->items[$r['id']])
	 				{
	 					$this->items[$r['id']]['selected'] = true;
	 					$this->rh->tpl->set('menu_selected', $r['id']);
	 				}
	 				else
	 				{
	 					$this->subItems[$r['_parent']]['childs'][$r['id']]['selected'] = true;
	 					$this->subItems[$r['_parent']]['selected'] = true;
	 					$this->items[$r['_parent']]['selected'] = true;
	 					$this->rh->tpl->set('menu_selected', $r['_parent']);
	 				}
	 			}
	 		}
 		}
 	}
 	 	
 }
?>