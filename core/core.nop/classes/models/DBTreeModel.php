<?php

$this->useClass('models/DBModel');

/**
 * ����� DBModelTree - ������� ����� �������, �������� � �� ���-�� �������������
 *
 * �������� ���� ��������������� �������, ����������� (�������) ����� .. 
 * 
 */
class DBTreeModel extends DBModel
{
	// �������� ���� �� ���������
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
		// �������� sql1 ��� ������ ��������� ;)
		// count() ��������
		return array($sql, $sql1);
	}

	/** ��������� ��� ������� 
	 *
	 * ����������:
	 *	  � ������� �� ������ ������ ���� ���� nested set
	 *	  �������� ����������
	 *	  _left � _level ��� ������������ ������
	 *
	 */
	function loadTree($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->data = NULL;

		// ��������� ���������� ������� �� _left
		array_unshift($this->order, '_left');
		$data = parent::select($where, $limit, $offset, true);
		$this->data = $this->buildTreeFromNestedSets($data);
	}

	/**
	 * ������ ������ �� ������ �������, � ����� nested sets
	 */
	function buildTreeFromNestedSets($rows)
	{
		if (empty($rows)) return array();
		$a_tree = array(); //���������
		$a_path = array(); //��������� �� ������� - ��������

		$root_node = $rows[0]; // ������ ������ � ������ -- ��� ������
		$i_level = intval($root_node['_level']);

		$a_path[$i_level] =& $a_tree;

		foreach ($rows as $f)
		{
			if ($f['_level'] > $i_level)
			{
				//���� �� ������ �� ������� ����, ��� ���� ������, ������:
				// - ����� ����������� ������ ����� �� 1 ������ ��������
				// - ��� ���������� ���� �� ���� ������� �� ���������� ������
				if ($f['_level'] != $i_level + 1 || !count($a_path[$i_level]))
					return false; //������ ���� �� ������ - ������ � ������

				$a_path[$f['_level']] =& 
					$a_path[$i_level][count($a_path[$i_level]) - 1][$name];
			}
			$i_level=intval($f['_level']);
			// �������� ���� 
			$name = $this->buildNodeName($f);
			$t = $f;
			$t[$name] = array();
			$a_path[$i_level][] = $t;
		}
		return $a_tree[0];
	}

	/**
	 * ������� �������� ����
	 */
	function buildNodeName($node)
	{
		return $this->node_name;
	}

}  

?>
