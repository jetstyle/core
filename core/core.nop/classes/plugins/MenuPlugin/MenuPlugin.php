<?php

$this->useClass('controllers/Plugin');

class MenuPlugin extends RenderablePlugin
{
	var $config_vars = array('template', 'store_to', 'level', 'depth', 'view', 'mode');
	var $view = 'main';
	var $mode = 'normal';
	var $level = 1;
	var $depth = 2;

	function getParentNodeByLevel($level)
	{
		$data = $this->rh->data;
		$sql = 'SELECT _path, _level, _left, _right FROM '. $this->rh->db_prefix .'content 
				WHERE _level = ' .$level 
				. ' AND ( '
				. '			(_left <= '.$data['_left'] . ' AND _right >= '.$data['_right'] .')'
				#. '	   OR (_right >= '.$data['_right'] .  ' AND _level = ' .$level .'))';
				. ')';
		$rs = $this->rh->db->queryOne($sql);
		return $rs;
	}
	function initialize()
	{
		if ($this->initialized) return;
		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$this->rh->UseClass("models/Menu");
		$menu =& new Menu($this->rh);
		switch($this->mode)
		{
		case 'submenu':
			$menu->level = $this->level;
			$menu->depth = $this->depth;
			$parent = $this->getParentNodeByLevel($this->level - 1);
			$menu->load(
				' AND _left > '.$parent['_left']
				.' AND _right < '.$parent['_right']
			);
			break;
		default:
			$menu->level = $this->level;
			$menu->depth = $this->depth;
			$menu->load();
		}

		$this->models['menu'] =& $menu;

		parent::initialize();
	}
	function addItem($item)
	{
		$this->models['menu']->data[] = $item;
	}

	function rend(&$ctx)
	{
		//вывод блока меню
		switch($this->view)
		{
		case 'tree':
			$this->rh->UseClass("plugins/MenuPlugin/MenuView");
			$v =& new MenuTreeView($this->rh);
			break;
		case 'list':
			$this->rh->UseClass("plugins/MenuPlugin/MenuView");
			$v =& new MenuListView($this->rh);
			break;
		default:
			$this->rh->UseClass("plugins/MenuPlugin/MenuView");
			$v =& new MenuView($this->rh);
		}
		$v->store_to = $this->store_to;
		$v->template = $this->template;
		$v->addModel($this->models['menu'], 'menu');
		$v->handle();
	}

}

?>
