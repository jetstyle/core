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
		$data = $this->getCurrent();
		if ($data)
		{
			$where = '{_level} = '.$level.' AND '.
					 '{_left} <= '.intval($data['_left']).' AND '.
					 '{_right} >= '.intval($data['_right']).' AND '.
					 '{_state} = 0';
			$result = DBModel::factory($this->config['model'])->loadOne($where)->getArray();
		}
		return $result;
	}

	public function getParentNodes()
	{
		if (null !== $this->parents)
		{
			return $this->parents;
		}

		$data = $this->getCurrent();

		if (!$data['id'])
		{
			$this->parents = array();
		}
		else
		{
			$where = '{_left} < '.$data['_left'].' AND '.
					 '{_right} >= '.$data['_right'].' AND '.
					 '{_level} < '.($this->config['level'] + $this->config['depth']).' AND '.
					 '{_state} = 0';
	
			$parents = $result = DBModel::factory($this->config['model'])->load($where)->getArray();
	
			if ($this->parents === null)
			{
				$this->parents = array();
			}
                        
                        foreach ($parents as $parent)
                        {
                                $this->parents[ $parent["id"] ] = $parent;
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
		if (!$this->config['model']) $this->config['model'] = 'Content/menu';

		$menu = & DBModel::factory($this->config['model']);
		//$menu->setOrder(array('_level' => 'ASC', '_order' => 'ASC'));

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

		$current = $this->getCurrent();
		if ($current)
		{
			$this->currentNodeId = $current['id'];
		}
		
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
	
	protected function getCurrent()
	{
		if (Locator::exists('controller'))
		{
			$data = &Locator::get('controller');
		}
		else
		{
			$data = array();
		}
		return $data;
	}
}
?>