<?php
Finder::useClass('blocks/Block');
class MenuBlock extends Block
{
	protected $parents = null;
	protected $items = array();
	protected $childs = array();

	public function addItem($item)
	{
		$data = &$this->getData();
		$data[] = $item;
	}

	public function &getElementById($id)
	{
		$data = &$this->getData();
		$item = &$this->findElementById($data, $id);
		return $item;
	}
	
	protected function getParentNodeByLevel($level)
	{
		$data = &Locator::get('controller');
		$sql = '
			SELECT id, _path, _level, _left, _right, _parent, hide_from_menu
			FROM ??content
			WHERE
				_level = ' . $level . '
					AND
				(_left <= ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . ')
					AND
				_state = 0
		';

		return Locator::get('db')->queryOne($sql);
	}

	protected function getParentNodes()
	{
		if (null !== $this->parents)
		{
			return $this->parents;
		}

		$data = &Locator::get('controller');

		if (!$data['id'])
		{
			$this->parents = array();
			return $this->parents;
		}

		$sql = '
			SELECT id, hide_from_menu, _parent
			FROM ??content
			WHERE _left < ' . $data['_left'] . ' AND _right >= ' . $data['_right'] . ' AND _level < '.($this->config['level'] + $this->config['depth']).' AND _state = 0
		';

		$this->parents = Locator::get('db')->query($sql, "id");

		if ($this->parents === null)
		{
			$this->parents = array();
		}

		return $this->parents;
	}

	protected function constructData()
	{
		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$menu = & DBModel::factory('Content');
		$menu->setOrder(array('_level' => 'ASC', '_order' => 'ASC'));

		$current = &Locator::get('controller');
		$parents = $this->getParentNodes();

		$where = array();

		$where[] = '('.$menu->quoteField('_level').' >= '.DBModel::quote($this->config['level']). ' AND '.$menu->quoteField('_level').' <'.DBModel::quote($this->config['level'] + $this->config['depth']).')';

		switch ($this->config['mode'])
		{
			case 'submenu' :

				$parent = $this->getParentNodeByLevel($this->config['level'] - 1);
				if (!$parent['id'])
				{
					$this->data = array();
					return;
				}
				$pid = $parent['id'];
				while ($p = $parents[$pid])
				{
					if ($p['hide_from_menu'])
					{
						$this->data = array();
						return;
					}
					$pid = $p['_parent'];
				}

				$where[] = $menu->quoteField('_left') .' > ' . DBModel::quote($parent['_left']);
				$where[] = $menu->quoteField('_right') .' < ' . DBModel::quote($parent['_right']);

			break;
		}

		$where[] = $menu->quoteField('hide_from_menu').' = 0';

		$menu = $menu->load(implode(' AND ', $where))->getArray();

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

			if ($r['controller'] == 'link')
			{
				$r['is_link'] = true;
//				if ($r['link_direct'])
//				{
//					$r['link'] = Link::formatLink($r['link']);
//				}
			}
			$r['href'] = $r['_path'];
			$this->childs[$r['_parent']][] = $r['id'];
			$this->items[$r['id']] = $r;
		}
		
		if(!empty($this->childs))
		{
			$data = $this->prepare(key($this->childs));
		}
		else
		{
			$data = array();
		}

		$this->setData($data);
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
		}
		return $childs;
	}

	protected function &findElementById(&$data, $id)
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
				if($item = &$this->findElementById($d['childs'], $id))
				{
					return $item;
				}
			}
		}
		return false;
	}
}
?>
