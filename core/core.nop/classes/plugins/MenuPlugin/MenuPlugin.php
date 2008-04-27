<?php
$this->useClass('controllers/Plugin');

class MenuPlugin extends RenderablePlugin
{
	var $config_vars = array (
		'template',
		'store_to',
		'level',
		'depth',
		'view',
		'mode'
	);
	var $view = 'main';
	var $mode = 'normal';
	var $level = 1;
	var $depth = 2;
	var $parents = array ();

	function getParentNodeByLevel($level)
	{
		$data = $this->rh->page->config;
		$sql = 'SELECT id, _path, _level, _left, _right FROM ' . $this->rh->db_prefix . 'content 
					WHERE _state = 0 AND _level = ' . $level . ' AND ( ' . '			(_left <= ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . ')' . ')';
		$rs = $this->rh->db->queryOne($sql);
		return $rs;
	}

	function getParentNodes()
	{
		if (!empty ($this->parents))
		{
			return $this->parents;
		}

		$data = $this->rh->page->config;

		if (!$data['id'])
			return;

		$sql = 'SELECT id FROM ' . $this->rh->db_prefix . 'content 
					WHERE _state = 0 AND _left < ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . '';
		$rs = $this->rh->db->query($sql);
		if (is_array($rs))
		{
			foreach ($rs AS $r)
			{
				$this->parents[$r['id']] = 1;
			}
		}

		return $this->parents;
	}

	function initialize(& $ctx, $config = NULL)
	{
		parent :: initialize($ctx, $config);
		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$this->rh->UseClass("models/MenuModel");
		$menu = & new MenuModel();

		$current = $this->rh->page->config;
		$parents = $this->getParentNodes();

		switch ($this->mode)
		{
			case 'submenu' :
				
				$parent = $this->getParentNodeByLevel($this->level - 1);
				if (!$parent['id'])
				{
					$this->models['menu']->data = array ();
					return;
				}
				$menu->level = $this->level;
				$menu->depth = $this->depth;
				$menu->left = $parent['_left'];
				$menu->right = $parent['_right'];
				$menu->initialize($this->rh);
				
//				$menu->load(' AND hide_from_menu = 0');
				$menu->load();
				
			break;
			
			default :
			
				$menu->level = $this->level;
				$menu->depth = $this->depth;
				$menu->initialize($this->rh);
//				$menu->load(' AND hide_from_menu = 0');
				$menu->load();
		}

		$this->rh->useClass('Link');
		$link = new Link($this->rh);
		$this->items = array ();

		if (is_array($menu->data))
		{
			foreach ($menu->data AS $i => $r)
			{
				if ($parents[$r['id']])
				{
					$r['selected'] = 1;
				}
				elseif ($r['id'] == $current['id'])
				{
					$r['current'] = 1;
				}

				if ($r['mode'] == 'link')
				{
					$r['is_link'] = true;
					if ($r['link_direct'])
					{
						$r['link'] = $link->formatLink($r['link']);
					}
				}
				
				$this->childs[$r['_parent']][] = $r['id'];
				$this->items[$r['id']] = $r;
			}
		}
		
		if(is_array($this->childs))
		{
			$menu->data = $this->prepare(key($this->childs));
		}
		unset ($this->items, $this->link, $this->childs);

		$this->models['menu'] = & $menu;
	}

	function prepare($id)
	{
		$childs = array ();
		if (is_array($this->childs[$id]))
		{
			foreach ($this->childs[$id] AS $r)
			{
				$this->items[$r]['childs'] = $this->prepare($r);
				$childs[] = $this->items[$r];
			}
		}
//		$keys = array_keys($childs);
//		$childs[$keys[0]]['is_first'] = true;
//		$childs[$keys[count($keys) - 1]]['is_last'] = true;
		$childs[0]['is_first'] = true;
		$childs[count($keys) - 1]['is_last'] = true;
		return $childs;
	}

	function addItem($item)
	{
		$this->models['menu']->data[] = $item;
	}

	function rend(& $ctx)
	{
		$this->rh->tpl->set($this->store_to, $this->models['menu']->data);
	}
	
	function &getData()
	{
		return $this->models['menu']->data;
	}
	
	function &findElementById($id)
	{
		$item = &$this->_findElementById($this->models['menu']->data, $id);
		return $item;
	}
	
	function &_findElementById(&$data, $id)
	{
		if(is_array($data))
		{
			foreach($data AS &$d)
			if($d['id'] == $id)
			{
				return $d;
			}
			elseif(is_array($d['childs']))
			{
				if($item = &$this->_findElementById($d['childs'], $id))
				{
					return $item;
				}
			}
		}
		return false;
	}
}
?>