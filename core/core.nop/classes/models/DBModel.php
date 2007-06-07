<?php

/**
 * Класс DBModel - базовый класс моделек, хранящих чего-то в БД
 *
 * Содержит кучу вспомогательных функций, облегчающих (надеюсь) жизнь .. 
 * 
 */
$this->useClass('models/Model');
$this->useClass('DBQueryParser');

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

		$this->_fields_info = array();
		$fields_info =& $this->_fields_info;
						
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
			// проверяем тип поля. и если не БД'шный -- перебрасываем в foreign_fields
			// (по умолчанию, если тип не указан, считаем что он БД'шный
			if (isset($fields_info[$field_name]['type'])
				// && $fields_info[$field_name]['type'] ! в списке типов полей из БД
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
		// теперь здесь только БД'шные поля
		// остальные -- в $this->foreign_fields
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
	 * Один-ко-многим или Многие-ко-многим
	 * $data -- массив с данными этой модели (который возвращает select(), 
	 * например)
	 * $info -- инфа о field
	 */
	function mapHasMany(&$data, $info)
	{
		$type = 'HasMany';
		if (isset($info[$type]['through'])) 
			// скорее всего многие - ко - многим
			return $this->mapHasManyThrough($data, $info);

		// один - ко - многим
		$field_name = $info['name'];
		$fk = $info[$type]['fk'];
		$pk = $info[$type]['pk'];
		$self_name = $info[$type]['name'];

		$model =& $this->$field_name;
		if (!isset($model)) return;

		if (1)
		{

		foreach ($data as $k=>$v)
		{
			//  lucky: этот load можно вынести "за скобки", и делать выборку для всех
			//  $pk из $data одним запросом. 
			//  тогда цикл можно сделать по результату $model->load
			//  lucky: done (см. ниже)
			//
			//  lucky: oups... так делать нельзя.
			//		 в $model могут быть специфичные ограничения (скажем $limit)
			//		 тогда результат будет неправильным
			$where = ' AND '.$model->quoteField($fk) .'='.$model->quote($v[$pk]);
			$model->load($where);

			$item = $model->data;
			foreach($item as $kk=>$vv) $item[$kk][$self_name] =& $data[$k];
			$data[$k][$field_name] = $item;
		}


		}
			/* lucky: нах. см. выше %)
		else
		{


		$pks = array();
		$datas = array();
		foreach ($data as $k=>$v)
		{
			$id = $data[$k][$pk];
			$pks[] = $id;
			$datas[$id] =& $data[$k];
		}
		$where = ' AND '.$model->quoteField($fk) .'IN ('.$model->quote($pks).')';
		unset($pks);

		$model->load($where);
		foreach ($model->data as $k=>$v)
		{
			$item = $model->data[$k];
			$f_id = $item[$fk];
			$my =& $datas[$f_id];
			$item[$self_name] =& $my;
			$my[$field_name] =& $item;
		}

		}
			 */
	}

	function buildJoin($fields)
	{
		$sql = '';
		$types = array('HasMany','HasOne');

		foreach ($fields as $v)
		{
			$info = $this->_fields_info[$v];
			$type = $info['type'];
			if (in_array($type, $types))
			{
				$field_name = $info['name'];
				$fk = $info[$type]['fk'];
				$pk = $info[$type]['pk'];
				$self_name = $info[$type]['name'];
				//$model =& $this->$field_name;
				//if (!isset($model)) continue;

				$t_table = $info[$type]['through']['name'];
				$t_pk = $info[$type]['through']['pk'];
				$t_fk = $info[$type]['through']['fk'];

				$sql .= 
				   ($info[$type]['only'] 
						? " INNER JOIN "
						: " LEFT JOIN "
					)
					. $this->buildTableNameAlias($t_table) 
					.	 "ON ("
					.		  $this->quoteField($pk)." = ".$t_table.".".$t_pk 
					.	     ")"
					;
			}
		}
		return $sql;
	}

	function mapHasManyThrough(&$data, $info)
	{
		$type = 'HasMany';
		$field_name = $info['name'];
		$fk = $info[$type]['fk'];
		$pk = $info[$type]['pk'];
		$self_name = $info[$type]['name'];

		$model =& $this->$field_name;
		if (!isset($model)) return;


		$t_table = $info[$type]['through']['name'];
		$t_pk = $info[$type]['through']['pk'];
		$t_fk = $info[$type]['through']['fk'];

		foreach ($data as $k=>$v)
		{
			$id = $v[$pk];
			$sql1 = 
				 " SELECT ". $model->buildFieldAliases($model->fields) 
				." FROM ".   $model->buildTableNameAlias($model->table)
				/*
				.  ($info['only'] 
						? " INNER JOIN "
						: " LEFT JOIN "
					)
				 */
				. " INNER JOIN "
				. $model->buildTableNameAlias($t_table) 
				.	 "ON ("
				.		  $model->quoteField($fk)." = ".$t_table.".".$t_fk 
				.	 " AND "
				.		  $this->quote($id)." = ".$t_table.".".$t_pk 
				.	     ")"
			. $model->buildWhere($where)
			. $model->buildGroupBy($model->group);
			$sql = $sql1
				. $model->buildOrderBy($model->order)
				. $model->buildLimit($limit, $offset);
			;
			// сохраним sql1 для будщих поколений ;)
			// count() например
			$sql_parts = array($sql, $sql1);

			$model->loadSql($sql_parts);
			foreach ($model->data as $kk=>$vv)
			{
				$ii =& $model->data[$kk];
				$ii[$self_name] =& $data[$k];
			}
			$data[$k][$field_name] = $model->data;
		}
	}


	function mapHasOne(&$data, $info)
	{
		$type = 'HasOne';
		$field_name = $info['name'];
		$fk = $info[$type]['fk'];
		$pk = $info[$type]['pk'];
		$self_name = $info[$type]['name'];

		$model =& $this->$field_name;
		if (!isset($model)) return;

		foreach ($data as $k=>$v)
		{
			$where = ' AND '.$model->quoteField($pk) .'='.$model->quote($v[$fk]);
			$model->load($where);
			$item = $model->data[0];
			if ($item)
			{
				$item[$kk][$self_name] =& $data[$k];
				$data[$k][$field_name] = $item;
			}
		}
	}

	/**
	 * Загрузить данные о файлах из аплоада
	 *
	 * FIXME: плохо, что для добавления новых типов
	 *			нужно править класс
	 */
	function mapUpload(&$data, $info)
	{
		$type = 'Upload';
		$field_name = $info['name'];
		$dir = $info[$type]['dir'];
		$name = $info[$type]['source'];
		$model =& $this->rh->upload;
		if (!isset($model)) return;

		if (isset($info[$type]['path']))
		{
			$pattern = $info[$type]['path']; 
		}
		else
		if (isset($info[$type]['dir']) && isset($info[$type]['file']))
		{
			$pattern = $info[$type]['dir'] .'/'.$info[$type]['file'];
		}

		$pattern = str_replace('*', '%s', $info[$type]['path']);

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

	function selectSql($sql_parts, $is_load=false)
	{
		list($sql, $sql1) = $sql_parts;
		if ($is_load) $this->sql = $sql1;
		$data = $this->rh->db->query($sql);
//		echo '<pre>';
//		var_dump($sql);
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
	function buildTableNameAlias($table, $alias=NULL)
	{
		$table_name_sql = $this->quoteName($this->rh->db_prefix.$table) .' AS '.$this->quoteName(($alias) ? $alias : $table);
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