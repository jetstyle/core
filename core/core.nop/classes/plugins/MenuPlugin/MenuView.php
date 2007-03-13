<?php

$this->UseClass("views/View");
/**
 * Класс MenuView - показывает меню
 *
 * Модели:
 * 'menu'	- спискок узлов сайта из DB.
 *				  атрибуты узлов:
 *					 _level
 *					 _path
 */
class MenuView  extends View
{
	var $store_to=NULL;

	function handle()
	{

		//if (!isset($_GET['lucky'])) return;
		$this->rh->UseClass("ListObject");

		$level_1 = array();
		$level_2 = array();
		// FIXME: lucky@npj - optimize it! 
		$segs = explode('/', $this->rh->data['_path']);
		$sel = $segs[0];
		foreach ($this->models['menu'] as $node)
		{
			if ($node['_level'] == 1)
			{
				$level_1[] = $node;
				#if (empty($sel)) $sel = $node['id'];
				if (empty($sel)) $sel = $node['_path'];
			} 
			elseif ($node['_level'] == 2 && strpos($node['_path'], $sel) === 0)
			{
				$level_2[] = $node;
			}
		}
		$list_1 =& new ListObject($this->rh, $level_1);
		$list_1->tpl_root = "_hp/menu.html:Menu";
		$list_1->store_to = False;
		$list_1->EVOLUTORS['class'] = array(&$this, "_getClass");
		$list_1->EVOLUTORS['suffix'] = array(&$this, "isParentSel");

		$list_2 =& new ListObject($this->rh, $level_2);
		$list_2->tpl_root = "_hp/menu.html:SubMenu";
		$list_2->store_to = False;
		$list_2->EVOLUTORS['suffix'] = array(&$this, "isParentSel");

		$out = $this->rh->tpl->set($this->store_to,
						$list_1->parse($list_1->tpl_root, $list_1->store_to)
						. $list_2->parse($list_2->tpl_root, $list_2->store_to)
					);

		return $out;
	}   

	function _getClass(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		switch ($list->loop_index)
		{
		case 0: $order = 'first'; break;
		case 1: $order = 'second'; break;
		default: $order = 'third';
		}
		return $order;
	}

	function isParentSel(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		$item['href'] = $this->rh->base_url.$item['_path'];
		//TODO: optimize it
		$path = implode('/',array_slice(explode('/',$item['_path']), 0, $item['_level']));
		$sel = (empty($path) ? '' : (strpos($this->rh->url, $path) === 0 ? '_Sel' : ''));
		if ($sel) $this->rh->tpl->set('_menu_level1_sel', $list->loop_index);
		return $sel;
	}

}

class MenuListView extends View
{

	function handle()
	{
		//if (!isset($_GET['lucky'])) return;
		$this->rh->UseClass("ListObject");
		$list =& new ListObject($this->rh, $this->models['menu']);
		$list->tpl_root = $this->template.':List';
		$list->store_to = $this->store_to;
		$list->EVOLUTORS['suffix'] = array(&$this, "isParentSel");
		$list->EVOLUTORS['class'] = array(&$this, "_getClass");
		$out = $list->parse($list->tpl_root, $list->store_to);
		return $out;
	}   

	function _getClass(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		switch ($list->loop_index)
		{
		case 0: $order = 'first'; break;
		case 1: $order = 'second'; break;
		default: $order = 'third';
		}
		return $order;
	}

	function isParentSel(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		$item['href'] = $this->rh->base_url.$item['_path'];
		//TODO: optimize it
		$path = array_slice(explode('/',$item['_path']), 0, $item['_level']);
		$url_path = array_slice(explode('/',$this->rh->url), 0, $item['_level']);
		return ($url_path === $path) ? 'Sel' : '';
	}
}
class MenuTreeView extends View
{
	function handle()
	{

		//if (!isset($_GET['lucky'])) return;
		$this->rh->UseClass("ListObject");

		$level = 0;
		$stack = array();
		foreach ($this->models['menu'] as $node)
		{
			if ($node['_level'] > 2) 
			{
				continue;
			}
			elseif ($node['_level'] > $level)
			{
				// вложенный уровень +1
				unset($a);
				$a = array();
				if (isset($list)) $stack[] =& $list;

				$list =& new ListObject($this->rh, $a);
				switch ($node['_level'])
				{
				case 1:
					$list->tpl_root = "_hp/menu.html:Menu";
					$list->store_to = $this->store_to;
					break;
				case 2:
				default:
					$list->tpl_root = "_hp/menu.html:SubMenu";
					$list->store_to = False;
				}
				$list->EVOLUTORS['suffix'] = array(&$this, "isParentSel");
			} 
			elseif ($node['_level'] < $level)
			{
				// обратно на один или несколько уровней
				while ($level > $node['_level'])
				{
					$list_parent =& array_pop($stack);
					$last = count($list_parent->ITEMS) - 1;
					$out = $list_parent->ITEMS[$last]['childs'] = 
						$list->parse($list->tpl_root, $list->store_to);
					$list =& $list_parent;
					$level -= 1;
				}
			}
			$level = $node['_level'];
			$list->ITEMS[] = $node;
		}
		// в стеке остались списки?
		while(($list_parent =& array_pop($stack)))
		{
			$last = count($list_parent->ITEMS) - 1;
			$list_parent->ITEMS[$last]['childs'] = 
				$list->parse($list->tpl_root, $list->store_to);
			$list =& $list_parent;
		}
		$list->EVOLUTORS['class'] = array(&$this, "_getClass");
		$out = $list->parse($list->tpl_root, $list->store_to);
		return $out;
	}   

	function _getClass(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		switch ($list->loop_index)
		{
		case 0: $order = 'first'; break;
		case 1: $order = 'second'; break;
		default: $order = 'third';
		}
		return $order;
	}

	function isParentSel(&$list)
	{
		$item =& $list->ITEMS[$list->loop_index];
		$item['href'] = $this->rh->base_url.$item['_path'];
		//TODO: optimize it
		$path = implode('/',array_slice(explode('/',$item['_path']), 0, $item['_level']));
		return (strpos($this->rh->url, $path) === 0 ? 'Sel' : '');
	}
}
