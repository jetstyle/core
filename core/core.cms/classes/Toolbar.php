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
 		$this->load();
 		$this->rh->tpl->set('menu', $this->items);
 		$this->rh->tpl->set('menu_submenu', $this->subItems);
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
	 		while($r = $this->rh->db->getRow($result))
	 		{
	 			if($r['_level'] == 1)
	 			{
	 				$this->items[] = $r;
	 			}
	 			else
	 			{
	 				if(!isset($this->subItems[$r['_parent']]))
	 				{
	 					$this->subItems[$r['_parent']] = array('id' => $r['_parent'], 'childs' => array());
	 				}
	 				$this->subItems[$r['_parent']]['childs'][] = $r;
	 			}
	 			
	 		}
 		}
 	}
 }
?>