<?php
Finder::useClass('blocks/Block');
class MenuBlock extends Block
{
	protected $parents = null;
	protected $currentNodeId = 0;
		
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
	
	public function markItem(&$model, &$row)
	{
		$parents = $this->getParentNodes();
		
		if ($parents[$row['id']])
		{
			$row['selected'] = 1;
		}
		elseif ($row['id'] == $this->currentNodeId)
		{
			$row['current'] = 1;
		}

		if ($row['controller'] == 'link')
		{
			$row['is_link'] = true;
		}
		$row['href'] = $row['_path'];
		
		if ($this->config['force_nbsp'])
		    $row['title_short'] = str_replace( " ", "&nbsp;", $row['title_short'] );
	}
	
	protected function getParentNodeByLevel($level)
	{
		$result = array();
		
		if (Locator::exists('controller'))
		{
			$data = &Locator::get('controller');
			$sql = '
				SELECT id, _path, _level, _left, _right, _parent, hide_from_menu
				FROM ??content
				WHERE
					_level = ' . $level . '
						AND
					(_left <= ' . intval($data['_left']) . ' AND _right >= ' . intval($data['_right']) . ')
						AND
					_state = 0
			';
	
			$result = Locator::get('db')->queryOne($sql);
		}
		
		return $result;
	}

	protected function getParentNodes()
	{
		if (null !== $this->parents)
		{
			return $this->parents;
		}

		if (Locator::exists('controller'))
		{
			$data = &Locator::get('controller');
		}
		else
		{
			$data = array();
		}

		if (!$data['id'])
		{
			$this->parents = array();
		}
		else
		{
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
		}

		return $this->parents;
	}

	protected function constructData()
	{
		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		$menu = & DBModel::factory('Content')->removeField('text');
		$menu->setOrder(array('_level' => 'ASC', '_order' => 'ASC'));

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

		if (Locator::exists('controller'))
		{
			$current = &Locator::get('controller');
			$this->currentNodeId = $current['id'];
		}
		
		$where[] = $menu->quoteField('hide_from_menu').' = 0';
		$menu->registerObserver('row', array(&$this, 'markItem'));
		$menu->loadTree(implode(' AND ', $where));

		$this->setData($menu->getArray());
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