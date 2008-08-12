<?php
Finder::useClass('controllers/Plugin');

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
	protected $parents = null;
	protected $items = array();
	protected $childs = array();

	protected function getParentNodeByLevel($level)
	{
		$data = &$this->rh->page->config;
		$sql = '
			SELECT id, _path, _level, _left, _right, hide_from_menu
			FROM ??content
			WHERE
				_level = ' . $level . '
					AND
				(_left <= ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . ')
					AND
				_state = 0
		';

		return $this->rh->db->queryOne($sql);
	}

	protected function getParentNodes()
	{
		if (null !== $this->parents)
		{
			return $this->parents;
		}

		$data = &$this->rh->page->config;

		if (!$data['id'])
		{
			$this->parents = array();
			return $this->parents;
		}

		$sql = '
			SELECT id, hide_from_menu
			FROM ??content
			WHERE _left < ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . ' AND _level < '.($this->level + $this->depth).' AND _state = 0
		';

		$this->parents = $this->rh->db->query($sql, "id");

		if ($this->parents === null)
		{
			$this->parents = array();
		}

		return $this->parents;
	}

	public function initialize(& $ctx, $config = NULL)
	{
		parent :: initialize($ctx, $config);

		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$menu = & DBModel::factory('Content');
		$menu->setOrder(array('_left' => 'ASC'));

		$current = &$this->rh->page->config;
		$parents = $this->getParentNodes();

		foreach ($parents AS $p)
		{
			if ($p['hide_from_menu'])
			{
				$this->models['menu'] = array ();
				return;
			}
		}

		$where = array();

		$where[] = '('.$menu->quoteField('_level').' >= '.DBModel::quote($this->level). ' AND '.$menu->quoteField('_level').' <'.DBModel::quote($this->level + $this->depth).')';

		switch ($this->mode)
		{
			case 'submenu' :

				$parent = $this->getParentNodeByLevel($this->level - 1);
				if (!$parent['id'] || $parent['hide_from_menu'])
				{
					$this->models['menu'] = array ();
					return;
				}

				$where[] = $menu->quoteField('_left') .' > ' . DBModel::quote($parent['_left']);
				$where[] = $menu->quoteField('_right') .' < ' . DBModel::quote($parent['_right']);

			break;
		}

		$where[] = $menu->quoteField('hide_from_menu').' = 0';

		$menu->load(implode(' AND ', $where));

		Finder::useClass('Link');
		$link = new Link($this->rh);

		foreach ($menu AS $i => $r)
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
			$r['href'] = $r['_path'];
			$this->childs[$r['_parent']][] = $r['id'];
			$this->items[$r['id']] = $r;
		}

		if(!empty($this->childs))
		{
			$this->models['menu'] = $this->prepare(key($this->childs));
		}

		unset ($this->items, $this->childs);
	}

	protected function prepare($id)
	{
		$childs = array ();
		if (is_array($this->childs[$id]))
		{
			foreach ($this->childs[$id] AS $r)
			{
				$this->items[$r]['childs'] = $this->prepare($r);
				$childs[] = $this->items[$r];
			}
			$childs[0]['is_first'] = true;
			$childs[count($keys) - 1]['is_last'] = true;
		}
		return $childs;
	}

	public function addItem($item)
	{
		$this->models['menu'][] = $item;
	}

	public function rend(& $ctx)
	{
		$this->rh->tpl->set($this->store_to, $this->models['menu']);
	}

	public function &getData()
	{
		return $this->models['menu'];
	}

	public function &findElementById($id)
	{
		$item = &$this->_findElementById($this->models['menu'], $id);
		return $item;
	}

	protected function &_findElementById(&$data, $id)
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