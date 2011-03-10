<?php
Finder::useClass('blocks/Block');
class MenuBlock extends Block
{
	protected $parents = null;
	protected $currentNodeId  = 0;
	protected $selectedNodeId = 0;

    /**
     * @param $this->config["mode"]     -   режим, submenu для всех подменю (те меню которые меняются от родителя)
     * @param $this->config["model"]    -   модель с данными
     * @param $this->config["level"]    -   уровень, начиная с которого показывать меню
     * @param $this->config["depth"]    -   на сколько уровней в глубину, начиная от level
     * @param $this->config["parent"]   -   для подменю узел-родитель
     */
	protected function constructData()
	{
        $this->data = array();
		/*
		 * загрузим модель меню
		 * с условием на where
		 */
		if (!$this->config['model']) $this->config['model'] = 'Content/menu';

		$menu = & DBModel::factory($this->config['model']);
		//$menu->setOrder(array('_level' => 'ASC', '_order' => 'ASC'));

		$parents = $this->getParentNodes();

		$where = array();

        //условие на уровень и глубину
		$where[] = '('.$menu->quoteField('_level').' >= '.DBModel::quote($this->config['level']). ' AND '.$menu->quoteField('_level').' <'.DBModel::quote($this->config['level'] + $this->config['depth']).')';

		switch ($this->config['mode'])
		{
			case 'submenu' :

                //родительский узел - либо из параметров вызова, либо из указанного уровня подменю-1
                if ( is_numeric( $this->getParam("parent") ) )
                    $parent = $this->getParentNodeById( $this->getParam("parent") );
                else if ( is_object( $this->getParam("parent") ) )
                    $parent = $this->getParam("parent");
                else
    				$parent = $this->getParentNodeByLevel($this->config['level'] - 1);

                //без родителя подменю не бывает
				if (!$parent['id'])	return;

				$pid = $parent['id'];

                //если кто-то из родительской цепочки помечен как "скрытый из меню" - то подменю пустое
				while ($p = $parents[$pid])
				{
					if ($p['hide_from_menu'])
					{
						return;
					}
					$pid = $p['_parent'];
				}

                //условие на поддерво
				$where[] = $menu->quoteField('_left') .' > '  . DBModel::quote($parent['_left']);
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

    /**
     * @param  $this->getCurrent() - текущий узел. обычно это текущий контроллер.
     * @return @this->parents - массив узлов из наддерева от текущего узла, уложенный по id
     */
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
	
    /**
     * @param $level - уровень с которого нам нужен родительский узел
     * @return       - родительский узел соответсвующего уровня
     **/
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

    /**
     * @param $pid - id узла 
     */
    protected function getParentNodeById($pid)
    {
        return DBModel::factory($this->config['model'])->loadOne("{id}=".DBModel::quote($pid))->getArray();
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
	
	public function setSelected($value){
	    $this->selectedNodeId = $value;
	}

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

	    if ($parents[$row['id']] || $this->selectedNodeId==$row['id'] )
	    {
		    $row['selected'] = 1;
	    }
		elseif ($row['id'] == $this->currentNodeId )
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


}
?>
