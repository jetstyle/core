<?php

/**
 * Класс DBModel - базовый класс моделек, хранящих чего-то в БД
 *
 * Содержит кучу вспомогательных функций, облегчающих (надеюсь) жизнь .. 
 * 
 */
$this->useClass('models/Model');
class DBModel extends Model
{
	/** имя таблицы */
	var $table=NULL;
	/** список полей таблицы */
	var $fields = array();
	/** список внешних полей */
	var $foreign_fields = array();
	var $fields_info= array();
	//var $lang_fields = array();
	/** условие where запроса */
	var $where = NULL;
	/** параметры GROUP BY запроса */
	var $group = NULL;
	/** параметры ORDER BY запроса */
	var $order = NULL;
	/** параметры LIMIT запроса */
	var $limit = NULL;
	var $offset = NULL;
	/** тут хранится select-from-where часть последнего SQL запроса, 
	 * который использовался для загрузки (load()) данных */
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

		// строим поля
		// на выходе:
		// feilds_info 
		// array(
		//		array('name', 'source', alias'),
		//		);

		$fields_info = array();
		if (isset($this->fields_info))
		{
			foreach ($this->fields_info as $v)
			{
				$field_name = $v['name']; // с точки зрения программы, в запросе это м.б. алиас
				$field_source = isset($v['source'])
					? $v['source'] 
					: $field_name;
				// грузим языкозависимые поля?
				if (array_key_exists('lang', $v) && $v['lang'] !== $ctx->lang)
				{
					continue; // не добавляем иностранные тексты
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
				$v['source'] = $this->_quoteField($field_source, $this->table);
				$v['alias'] = $this->_quoteField($field_alias);
				$fields_info[$field_name] = $v;
			}
		}
		foreach ($this->fields as $field_name)
		{
			if (!array_key_exists($field_name, $fields_info))
			{
				$info = array(
					'name' => $field_name,
					'source' => $this->_quoteField($field_name, $this->table),
					'alias' => NULL,
				);
				$fields_info[$field_name] = $info;
			}
		}
		$this->_fields_info = $fields_info;

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
	 * $data -- массив с данными этой модели (который возвращает select(), 
	 * например)
	 * $info -- инфа о field
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
		// сохраним sql1 для будщих поколений ;)
		// count() например
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
				$_where[] = $this->quoteField($field) .'='.$this->quote($row[$field]);
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
				$w[] = $this->quoteField($k) . '='. $this->quoteValue($v);
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
		$fields_sql = $this->buildFields(array_keys($t));
		$values_sql = $this->buildValues(array_values($t));
	}
	function buildFieldsValuesSet($data, &$set_sql)
	{
		$set = array(); 
		foreach ($data as $k=>$v)
			$set[] = $this->quoteField($k) . '='. $this->quoteValue($v);
		$set_sql = implode(',', $set);
	}

	function buildFieldAliases($fields)
	{
		$fields_sql = implode(',', array_map(array(&$this, 'quoteFieldAlias'), $fields));
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

		return $info['source'];
	}
	function quoteOrderField($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return $info['source'].(isset($info['order']) 
			? ' '.$info['order'] 
			: '');
	}
	function quoteFieldAlias($name)
	{
		$info =& $this->_fields_info[$name];
		if (!isset($info)) return NULL;

		return isset($info['alias']) 
			?  $info['source'].' AS '.$info['alias']
			: $info['source'];
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

}  

?>
