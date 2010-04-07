<?php


class DBModelTree extends DBModel
{

	public function &load($where=NULL, $limit=NULL, $offset=NULL)
	{
		if ($limit == 1)
		{
			parent::load($where);
		}
		else
		{
			$this->treeMinLevel = null;
			$this->treeRootId = null;

			/**
			 * we need to aggregate data by primary key
			 */
			if (!$this->keyField)
			{
				$this->setKeyField($this->getPk());
			}
			$this->registerObserver('row', array($this, 'treePrepareRow'));
			$this->registerObserver('did_load', array($this, 'treeConstruct'));

			parent::load($where);

			$this->removeObserver('row', array($this, 'treePrepareRow'));
			$this->removeObserver('did_load', array($this, 'treeConstruct'));
		}

		return $this;
	}

	protected function onBeforeInsert(&$row)
	{
		if (!isset($row['_parent']))
		{
			$row['_parent'] = 0;
		}

		if ($row['_parent'])
		{
			$db = Locator::get('db');
			$queryResult = $db->queryOne("
				SELECT ".$this->getPk()."
				FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
				WHERE ".$this->quoteName('_parent')." = ".$this->quoteValue($row['_parent'])."
			");

			if (!$queryResult[$this->getPk()])
			{
				$row['_parent'] = 0;
			}
		}

		if (!isset($row['_order']))
		{
			$db = Locator::get('db');

			$orderResult = $db->queryOne("
				SELECT (MAX(".$this->quoteName('_order').") + 1) AS _max
				FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
				WHERE ".$this->quoteName('_parent')." = ".$this->quoteValue($row['_parent'])."
			");

			$row['_order'] = intval($orderResult['_max']);
		}

		if (!isset($row['_level']))
		{
			if ($row['_parent'])
			{
				$db = Locator::get('db');
				$queryResult = $db->queryOne("
					SELECT _level
					FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
					WHERE ".$this->quoteName('_parent')." = ".$this->quoteValue($row['_parent'])."
				");
				$row['_level'] = intval($row['_level']) + 1;
			}
			else
			{
				$row['_level'] = 1;
			}
		}

		if (!$row['_supertag'] && $row['title'])
		{
			Finder::useClass('Translit');
			$translit = new Translit();
			$row['_supertag'] = $translit->supertag($row['title'], 20);
		}
	}

	protected function onAfterInsert(&$row)
	{
		$this->rebuild();
	}

	public function delete($where)
	{
		$affectedRows = 0;

		$db = Locator::get('db');

		$this->usePrefixedTableAsAlias = true;

		if ($where)
		{
			$where = 'WHERE '.$this->parse($where);
		}

		$sqlResult = $db->execute("
			SELECT id, _left, _right
			FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
			".$where );

		while ($node = $db->getRow($sqlResult))
		{
			$db->query("
				DELETE FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
				WHERE _left >= ".self::quote($node['_left'])." AND _right <= ".self::quote($node['_right'])."
			");

			$affectedRows += $db->affectedRows();
		}

		$this->usePrefixedTableAsAlias = false;

		$this->rebuild();

		return $affectedRows;
	}

	public function deleteNode($nodeId)
	{
		if (!$nodeId)
		{
			return 0;
		}

		return $this->delete('{'.$this->getPk().'} = '.self::quote($nodeId));
	}

	public function getItems()
	{
		return $this->items;
	}

	public function getChildren()
	{
		return $this->children;
	}

	public function rebuild()
	{
		$db = Locator::get('db');
		$sqlResult = $db->execute("
			SELECT ".$this->getPk().", _parent, _level, _supertag, _path
			FROM ".$this->quoteName(($this->autoPrefix ? DBAL::$prefix : "").$this->getTableName())."
			ORDER BY _level ASC, _order ASC
		");

		$items = array();
		$children = array();

		while ($r = $db->getRow($sqlResult))
		{
			$items[$r[$this->getPk()]] = $r;
			$children[$r['_parent']][] = $r[$this->getPk()];
		}

		return $this->rebuildTree($items, $children);
	}

	protected function rebuildTree(&$items, &$children, $parentId = 0, $left = 0, $order = 0)
	{
		//shortcuts
		$node = &$items[ $parentId ];
		$db = Locator::get('db');

		if ($node[$this->getPk()])
		{
			if (is_array($items[ $node['_parent'] ]))
			{
				$node['_path'] = ($items[$node['_parent']]['_path'] ? $items[$node['_parent']]['_path'].'/' : '').$node['_supertag'];

				if (array_key_exists('_level', $items[ $node['_parent'] ]))
				{
					$node['_level'] = $items[ $node['_parent'] ]['_level'] + 1;
				}
			}
			else
			{
				$node['_path'] = $node['_supertag'];
			}
		}

		/* Taken from http://www.sitepoint.com/article/1105/3 */

		// the right value of this node is the left value + 1
		$right = $left + 1;

		$n = count($children[$parentId]);
		for ($i = 0; $i < $n; $i++)
		{
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = $this->rebuildTree( $items, $children, $children[$parentId][$i], $right, $i);
		}

		if ($node[$this->getPk()])
		{
			// we've got the left value, and now that we've processed
			// the children of this node we also know the right value
			$node['_left'] = $left;
			$node['_right'] = $right;

			$this->update($node, '{'.$this->getPk().'} = '.self::quote($node[$this->getPk()]));
		}

		// return the right value of this node + 1
		return $right + 1;
	}
}

?>