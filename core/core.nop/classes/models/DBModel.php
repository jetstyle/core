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
	/** условие where запроса */
	var $where = NULL;
	/** параметры ORDER BY запроса */
	var $order = NULL;
	/** параметры LIMIT запроса */
	var $limit = NULL;
	var $offset = NULL;
	/** тут хранится select-from-where часть последнего SQL запроса, 
	 * который использовался для загрузки (load()) данных */
	var $sql = NULL;

	function initialize()
	{
		foreach (array('before_load') as $v)
		{
			$this->registerObserver($v, $this->config[$v]);
		}
		foreach (array('fields', 'where', 'order', 'limit', 'offset') as $v)
		{
			if (isset($this->config[$v])) $this->$v = $this->config[$v];
		}
		return True;
	}
	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->notify('before_load', array(&$this));
		$this->data = $this->select($where, $limit, $offset, true);
		$this->notify('load', array(&$this));
	}

	function getSelectSql($where=NULL, $limit=NULL, $offset=NULL)
	{
		$sql1 =  ' SELECT ' . $this->buildFields($this->fields)
				. ' FROM '   . $this->buildTableName($this->table)
				. $this->buildWhere($where);
		$sql = $sql1
				. $this->buildOrderBy($this->order)
				. $this->buildLimit($limit, $offset);
			;
		// сохраним sql1 для будщих поколений ;)
		// count() например
		return array($sql, $sql1);
	}
	function select($where=NULL, $limit=NULL, $offset=NULL, $is_load=false)
	{
		list($sql, $sql1) = $this->getSelectSql($where, $limit, $offset, $is_load);
		if ($is_load) $this->sql = $sql1;
		return $this->rh->db->query($sql);
	}
	function insert(&$row)
	{
		if (in_array('_created', $this->fields) && !array_key_exists('_created', $row))
			$row['_created'] = date('Y-m-d H:i:s');
		$this->buildFieldsValues($row, $fields_sql, $values_sql);
		$sql = ' INSERT INTO '.$this->buildTableName($this->table)
				.'('.$fields_sql.')'
				.' VALUES ('.$values_sql.')';
		$row['id'] = $this->rh->db->insert($sql);
		return $id;
	}
	function update(&$row, $where=NULL)
	{
		if (in_array('_updated', $this->fields) && !array_key_exists('_updated', $row))
			$row['_updated'] = date('Y-m-d H:i:s');
		$this->buildFieldsValuesSet($row, $fields_sql);
		$sql = ' UPDATE '.$this->buildTableName($this->table)
				.' SET '.$fields_sql
				.' WHERE '.$where;
		return $this->rh->db->query($sql);
	}
	function delete($row)
	{
		$where = array(); 
		foreach ($row as $k=>$v)
			$where[] = $this->quoteField($k) . '='. $this->quoteValue($v);
		$where_sql = implode(' AND ', $where);
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
		$fields_sql = $this->buildFields(array_keys($data));
		$values_sql = $this->buildValues(array_values($data));
	}
	function buildFieldsValuesSet($data, &$set_sql)
	{
		$set = array(); 
		foreach ($data as $k=>$v)
			$set[] = $this->quoteField($k) . '='. $this->quoteValue($v);
		$set_sql = implode(',', $set);
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
	function buildOrderBy($fields)
	{
		if (empty($fields))
			$orderby_sql = '';
		else
			$orderby_sql = ' ORDER BY '. (
				is_array($fields)
					? implode(',',array_map(array(&$this, 'quoteName'), $fields))
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
		if (strpos($name, ' ')) return $name;
		return implode('.', array_map(array(&$this, 'quoteName'), 
		explode('.', $name)));
	}

}  

?>
