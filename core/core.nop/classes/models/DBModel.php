<?php

/**
 * ����� DBModel - ������� ����� �������, �������� ����-�� � ��
 *
 * �������� ���� ��������������� �������, ����������� (�������) ����� .. 
 * 
 */
$this->useClass('models/Model');
$this->useClass('DBQueryParser');

class DBModel extends Model
{
	/** ��� ������� */
	var $table=NULL;
	/** ������ ����� ������� */
	var $fields = array();
	/** ������ ������� ����� */
	var $foreign_fields = array();
	var $fields_info= array();
	//var $lang_fields = array();
	/** ������� where ������� */
	var $where = NULL;
	/** ��������� GROUP BY ������� */
	var $group = NULL;
	/** ��������� ORDER BY ������� */
	var $order = NULL;
	/** ��������� LIMIT ������� */
	var $limit = NULL;
	var $offset = NULL;
	/** ��� �������� select-from-where ����� ���������� SQL �������, 
	 * ������� ������������� ��� �������� (load()) ������ */
	var $sql = NULL;

	function initialize(&$ctx, $config=NULL)
	{
		$parent_status = parent::initialize($ctx, $config);

		foreach (array('before_load') as $v)
		{
			if (isset($config[$v])) $this->registerObserver($v, $this->config[$v]);
		}
		foreach (array('fields', 'where', 'order', 'limit', 'offset') as $v)
		{
			if (isset($this->config[$v])) $this->$v = $this->config[$v];
		}

		// ������ ����
		// �� ������:
		// feilds_info 
		// array(
		//		array('name', 'source', alias'),
		//		);

		$this->_fields_info = array();
		$fields_info =& $this->_fields_info;

		if (isset($this->fields_info))
		{
			foreach ($this->fields_info as $v)
			{
				$field_name = $v['name']; // � ����� ������ ���������, � ������� ��� �.�. �����
				$field_source = isset($v['source'])
					? $v['source'] 
					: $field_name;
				// ������ �������������� ����?
				if (array_key_exists('lang', $v) && $v['lang'] !== $ctx->lang)
				{
					continue; // �� ��������� ����������� ������
				}
				$field_alias = (isset($v['alias']) 
					? $v['alias']
					: (
						($field_source === $field_name) 
						? NULL
						: $field_name
					)
				);
				$v['name'] = $field_name;
				$v['source_full'] = $this->parse($this->_quoteField($field_source, $this->table));
				$v['source'] = $this->parse($this->_quoteField($field_source));
				$v['alias'] = $this->_quoteField($field_alias);
				$fields_info[$field_name] = $v;
			}
		}
		$fields = array();
		foreach ($this->fields as $field_name)
		{
			if (!array_key_exists($field_name, $fields_info))
			{
				$info = array(
					'name' => $field_name,
					'source_full' => $this->parse($this->_quoteField($field_name, $this->table)),
					'source' => $this->parse($this->_quoteField($field_name)),
					'alias' => NULL,
				);
				$fields_info[$field_name] = $info;
			}
			// ��������� ��� ����. � ���� �� ��'���� -- ������������� � foreign_fields
			// (�� ���������, ���� ��� �� ������, ������� ��� �� ��'����
			if (isset($fields_info[$field_name]['type'])
				// && $fields_info[$field_name]['type'] ! � ������ ����� ����� �� ��
				&& !in_array($field_name, $this->foreign_fields)
				)
			{
				$this->foreign_fields[] = $field_name;
			}
			else
			{
				$fields[] = $field_name;
			}
		}
		//$this->_fields_info = $fields_info;
		// ������ ����� ������ ��'���� ����
		// ��������� -- � $this->foreign_fields
		$this->fields = $fields;

		return $parent_status && True;
	}
	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->notify('before_load', array(&$this));
		$this->data = $this->select($where, $limit, $offset, true);
		$this->notify('load', array(&$this));
	}
	function loadSql($sql)
	{
		$this->notify('before_load', array(&$this));
		$this->data = $this->selectSql($sql, true);
		$this->notify('load', array(&$this));
	}

	// relations
	/**
	 * $data -- ������ � ������� ���� ������ (������� ���������� select(), 
	 * ��������)
	 * $info -- ���� � field
	 */
	function mapHasMany(&$data, $info)
	{
		$field_name = $info['name'];
		$fk = $info['has_many']['fk'];
		$pk = $info['has_many']['pk'];
		$self_name = $info['has_many']['name'];


		$model =& $this->$field_name;
		if (!isset($model)) return;

		foreach ($data as $k=>$v)
		{
			$where = ' AND '.$model->quoteField($fk) .'='.$model->quote($v[$pk]);
			$model->load($where);
			$item = $model->data;
			foreach($item as $kk=>$vv) $item[$kk][$self_name] =& $data[$k];
			$data[$k][$field_name] = $item;
		}
	}


	/**
	 * ��������� ������ � ������ �� �������
	 *
	 * FIXME: �����, ��� ��� ���������� ����� �����
	 *			����� ������� �����
	 */
	function mapUpload(&$data, $info)
	{
		$field_name = $info['name'];
		$dir = $info['dir'];
		$name = $info['source'];
		$model =& $this->rh->upload;

		if (!isset($model)) return;

		if (isset($info['path']))
		{
			$pattern = $info['path']; 
		}
		else
		if (isset($info['dir']) && isset($info['file']))
		{
			$pattern = $info['dir'] .'/'.$info['file'];
		}

		$pattern = str_replace('*', '%s', $info['path']);

		foreach ($data as $k=>$v)
		{
			$fname = sprintf($pattern, $v['id']);
			$file = $this->rh->upload->getFile($fname);
			if ($file) 
			{
				list($width, $height, $type, $attr) = getimagesize($file->name_full);
				$file->height = $height;
				$file->width = $width;
			}
			$data[$k][$field_name] = $file;
		}
	}

	function getSelectSql($where=NULL, $limit=NULL, $offset=NULL)
	{
		$sql1 =  ' SELECT ' . $this->buildFieldAliases($this->fields)
			. ' FROM '   . $this->buildTableNameAlias($this->table)
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
	function selectSql($sql_parts, $is_load=false)
	{
		list($sql, $sql1) = $sql_parts;
		if ($is_load) $this->sql = $sql1;
		$data = $this->rh->db->query($sql);
		$this->loadForeignFields($data);
		return $data;
	}

	function loadForeignFields(&$data)
	{
		foreach ($this->foreign_fields as $v)
		{
			$info = $this->_fields_info[$v];
			if (isset($info['type']))
			{
				$method_name = 'map'.$info['type'];
				$this->$method_name($data, $info);
			}
			// lucky: compability -- remove it
			else
			if (isset($info['has_many']))
			{
				$this->mapHasMany($data, $info);
			}
		}
	}

	function select($where=NULL, $limit=NULL, $offset=NULL, $is_load=false)
	{
		$sql = $this->getSelectSql($where, $limit, $offset, $is_load);
		return $this->selectSql($sql, $is_load);
	}
	function onBeforeInsert(&$row)
	{
		if (array_key_exists('_created', $this->_fields_info) 
			&& !array_key_exists('_created', $row))
				$row['_created'] = date('Y-m-d H:i:s');
	}
	function onBeforeUpdate(&$row)
	{
		if (array_key_exists('_modified', $this->_fields_info) 
			&& !array_key_exists('_modified', $row))
				$row['_modified'] = date('Y-m-d H:i:s');
	}
	function insert(&$row)
	{
		$this->onBeforeInsert($row);
		$this->buildFieldsValues($row, $fields_sql, $values_sql);
		$sql = ' INSERT INTO '.$this->buildTableName($this->table)
			.'('.$fields_sql.')'
			.' VALUES ('.$values_sql.')';
		$row['id'] = $this->rh->db->insert($sql);
		return $row['id'];
	}
	function update(&$row, $where=NULL)
	{
		if (is_array($where)) 
		{
			$_where = array();
			foreach($where as $field)
			{
				$_where[] = $this->quoteFieldShort($field) .'='.$this->quote($row[$field]);
			}
			$where = implode(' AND ', $_where);
			unset($_where);
		}
		$this->onBeforeUpdate($row);
		$this->buildFieldsValuesSet($row, $fields_sql);
		$sql = ' UPDATE '.$this->buildTableName($this->table)
			.' SET '.$fields_sql
			.' WHERE '.$where;
		return $this->rh->db->query($sql);
	}
	function delete($where)
	{
		if (is_array($where))
		{
			$w = array(); 
			foreach ($where as $k=>$v)
				$w[] = '('
					.(isset($v) 
						?  $this->quoteFieldShort($k) . '='. $this->quoteValue($v)
						:  $this->quoteFieldShort($k) . ' IS NULL '
					)
					.')'
					;
			$where_sql = implode(' AND ', $w);
		}
		else
		{
			$where_sql = $where;
		}

		$sql = 'DELETE FROM '.$this->buildTableName($this->table)
			.$this->buildWhere(' AND '.$where_sql);
		return $this->rh->db->query($sql);
	}

	function clean($truncate=True)
	{
		switch ($truncate)
		{
		case True:  $sql = ' TRUNCATE TABLE ' .$this->buildTableName($this->table); 
			break;
		case False: $sql = ' DELETE FROM ' .$this->buildTableName($this->table); 
			break;
		default:    $sql = NULL;
		}

		if (isset($sql)) return $this->rh->db->query($sql);
		return False;
	}
	function count($where=NULL)
	{
		if (!isset($this->sql)) list(,$sql) = $this->getSelectSql($where);
		else $sql = $this->sql;

		if (!preg_match('#(\sFROM\s.+)$#ms', $sql, $matches)) return NULL;
		$sql = 'SELECT COUNT(*) AS `cnt` '.$matches[1];
		$rs = $this->rh->db->query($sql);
		// FIXME: cache it
		$count = intval($rs[0]['cnt']);
		return $count;
	}

	function buildLimit($limit=NULL, $offset=NULL)
	{
		$limit = isset($limit) ? $limit : $this->limit;
		$offset = isset($offset) ? $offset : $this->offset;
		if ($limit && $offset)
			$limit_sql = " LIMIT ".$offset.",".$limit;   

		else if ($limit)
			$limit_sql = " LIMIT ".$limit;

		return $limit_sql;
	}

	function buildFieldsValues($data, &$fields_sql, &$values_sql)
	{
		// lucky: FIXME: filter row
		$t = array();
		foreach ($data as $k=>$v)
			if (array_key_exists($k, $this->_fields_info)) 
				$t[$k] = $v;
		$fields_sql = $this->buildFieldsShort(array_keys($t));
		$values_sql = $this->buildValues(array_values($t));
	}
	function buildFieldsValuesSet($data, &$set_sql)
	{
		$set = array(); 
		foreach ($data as $k=>$v)
			$set[] = $this->quoteFieldShort($k) . '='. $this->quoteValue($v);
		$set_sql = implode(',', $set);
	}

	function buildFieldAliases($fields)
	{
		$fields_sql = implode(',', array_map(array(&$this, 'quoteFieldAlias'), $fields));
		return $fields_sql;
	}
	function buildFieldsShort($fields)
	{
		$fields_sql = implode(',', array_map(array(&$this, 'quoteFieldShort'), $fields));
		return $fields_sql;
	}
	function buildFields($fields)
	{
		$fields_sql = implode(',', array_map(array(&$this, 'quoteField'), $fields));
		return $fields_sql;
	}
	function buildValues($values)
	{
		$values_sql = implode(',', array_map(array(&$this, 'quoteValue'), $values));
		return $values_sql;
	}
	function quoteValues($values)
	{
		return $this->buildValues($values);
	}
	function buildTableName($table)
	{
		$table_name_sql = $this->quoteName($this->rh->db_prefix.$table);
		return $table_name_sql;
	}
	function buildTableNameAlias($table)
	{
		$table_name_sql = $this->quoteName($this->rh->db_prefix.$table) .' AS '.$this->quoteName($table);
		return $table_name_sql;
	}
	function buildWhere($where)
	{
		if (isset($this->where)) 
			$where_sql = $this->where;
		else
			$where_sql = '';

		if ($where || $where_sql)
			$where_sql = ' WHERE ' . $where_sql . $where;
		else
			$where_sql = '';
		return $where_sql;
	}
	function buildGroupBy($fields)
	{
		if (empty($fields))
			$sql = '';
		else
			$sql = ' GROUP BY '. (
				is_array($fields)
				? implode(',',array_map(array(&$this, 'quoteField'), $fields))
				:	$fields)
				;
		return $sql;
	}
	function buildOrderBy($fields)
	{
		if (empty($fields))
			$orderby_sql = '';
		else
			$orderby_sql = ' ORDER BY '. (
				is_array($fields)
				? implode(',',array_map(array(&$this, 'quoteOrderField'), $fields))
				:	$fields)
				;
		return $orderby_sql;
	}

	function quote($str)
	{
		$str_sql = $this->rh->db->quote($str);
		return $str_sql;
	}
	function quoteValue($value)
	{
		return (isset($value) ?  $this->quote($value) : 'NULL');
	}
	function quoteName($name)
	{
		if ($name !== '*') 
			$name_sql = '`'.$name.'`';
		else 
			$name_sql = $name;

		return $name_sql;
	}
	function quoteField($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return $info['source_full'];
	}
	function quoteFieldShort($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return $info['source'];
	}
	function quoteOrderField($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return $info['source_full'].(isset($info['order']) 
			? ' '.$info['order'] 
			: '');
	}
	function quoteFieldAlias($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return isset($info['alias']) 
			?  $info['source_full'].' AS '.$info['alias']
			: $info['source_full'];
	}

	function _quoteField($name, $table_name=NULL)
	{
		if (!isset($name) || strpos($name, ' ')) return $name;
		return implode('.', 
			array_map(array(&$this, 'quoteName'), 
			explode('.',  (
				(strpos('.', $name) === false && isset($table_name))
					? $table_name.'.'.$name 
					: $name
			))));
	}

	function parse($query)
	{
		$args = func_get_args();
		$query = array_shift($args);
		$parser =& new DBQueryParser();
		$parser->factory =& $this;
		$parser->params =& $args;
		$parser->initialize($this->rh);
		$sql = $parser->parse($query);
		return $sql;
	}

}  


?>
