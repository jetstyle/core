<?php

$this->useClass('models/DBModel');

/**
 * Класс DBModelTree - базовый класс моделек, хранящих в БД что-то древоподобное
 *
 * Содержит кучу вспомогательных функций, облегчающих (надеюсь) жизнь .. 
 * 
 */
class DBTreeModel extends DBModel
{
	// название узла по умолчанию
	var $node_name = 'tree';

	var $order = array('_left');

	function getSelectSql($where=NULL, $limit=NULL, $offset=NULL)
	{
		$ta = $this->quoteName($this->table);
		$use_parent = strpos($where, 'parent') !== false;

		if (!isset($where)) $where = '';
		if ($use_parent)
			$where .= 
				' AND ('.$ta.'._left >= parent._left AND '.$ta.'._right <= parent._right) ';
		$sql1 =  ' SELECT ' . $this->buildFieldAliases($this->fields)
			. ' FROM '   . $this->buildTableNameAlias($this->table)
			. ($use_parent ? ' ,' . $this->buildTableNameAlias($this->table, 'parent') : '')
			//. $this->buildJoin($this->foreign_fields)
			. $this->buildWhere($where)
			. $this->buildGroupBy($this->group);
		$sql = $sql1
			. $this->buildOrderBy($this->order)
			. $this->buildLimit($limit, $offset);
		;
		// сохраним sql1 для будщих поколений ;)
		// count() например
		return array($sql, $sql1);
	}

	/** загрузить как таблицу 
	 *
	 * реализация:
	 *	  в таблице БД дерево должно быть виде nested set
	 *	  алгоритм использует
	 *	  _left и _level для формирования дерева
	 *
	 */
	function loadTree($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->data = NULL;

		// форсируем сортировку сначала по _left
		array_unshift($this->order, '_left');
		$data = parent::select($where, $limit, $offset, true);
		$this->data = $this->buildTreeFromNestedSets($data);
	}

	/**
	 * Строит дерево по списку записей, в форме nested sets
	 */
	function buildTreeFromNestedSets($rows)
	{
		if (empty($rows)) return array();
		$a_tree = array(); //Результат
		$a_path = array(); //Указатели на вершины - родители

		$root_node = $rows[0]; // первая запись в списке -- это корень
		$i_level = intval($root_node['_level']);

		$a_path[$i_level] =& $a_tree;

		foreach ($rows as $f)
		{
			if ($f['_level'] > $i_level)
			{
				//Если мы попали на уровень выше, чем были раньше, значит:
				// - номер предыдущего уровня ровно на 1 меньше текущего
				// - уже обработана хотя бы одна вершина на предыдущем уровне
				if ($f['_level'] != $i_level + 1 || !count($a_path[$i_level]))
					return false; //Такого быть не должно - ошибка в дереве

				$a_path[$f['_level']] =& 
					$a_path[$i_level][count($a_path[$i_level]) - 1][$name];
			}
			$i_level=intval($f['_level']);
			// название узла 
			$name = $this->buildNodeName($f);
			$t = $f;
			$t[$name] = array();
			$a_path[$i_level][] = $t;
		}
		return $a_tree[0];
	}

	/**
	 * Вернуть название узла
	 */
	function buildNodeName($node)
	{
		return $this->node_name;
	}

}  

?>
