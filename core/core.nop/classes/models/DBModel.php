<?php

//интерфейс получения даных от объекта. используется в Controller::add_config, ну и вообще везде где надо определить косит ли объект под массив. (с) dz
interface DataContainer
{
	public function &getData();
}

/**
 * Класс DBModel - базовый класс моделек, хранящих чего-то в БД
 *
 * Содержит кучу вспомогательных функций, облегчающих (надеюсь) жизнь .. 
 * 
 */
$this->useClass('models/Model');
$this->useClass('models/ResultSet');
$this->useClass('DBQueryParser');
$this->useClass("Inflector");

class DBModel extends Model implements IteratorAggregate, ArrayAccess, Countable, DataContainer
{
	/** имя таблицы */
	var $table=NULL;
	/** список полей таблицы */
	var $fields = array('*');
	/** список внешних полей */
	var $foreign_fields = array();
	var $fields_info= array();
	//var $lang_fields = array();
	/** условие where запроса */
	var $where = NULL;
	/** параметры GROUP BY запроса */
	var $group = NULL;
	/** параметры ORDER BY запроса */
	var $order = array('id' => 'DESC');
	/** параметры LIMIT запроса */
	var $limit = NULL;
	var $offset = NULL;
	/** тут хранится select-from-where часть последнего SQL запроса, 
	 * который использовался для загрузки (load()) данных */
	var $sql = NULL;
	var $has_one = NULL; 
	var $is_initialized = false;

	function initialize(&$ctx, $config=NULL)
	{
		$this->is_initialized = true; //иногда создаем объект, а потом делаем "initialize"

		$parent_status = parent::initialize($ctx, $config);

		if (is_null($this->table))
			$this->table = $this->autoDefineTable();

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

//		$this->makeHasOneConfig();
		$this->makeForeignsConfig();

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

		$this->_fields_info = $fields_info;
		// теперь здесь только БД'шные поля
		// остальные -- в $this->foreign_fields
		$this->fields = $fields;
		return $parent_status && True;
	}
	function autoDefineTable()
	{
		return Inflector::underscore(get_class($this));
	}

	function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->notify('before_load', array(&$this));
//		$this->data = $this->select($where, $limit, $offset, true);
		$this->setData($this->select($where, $limit, $offset, true));
		$this->notify('load', array(&$this));
	}
	function loadSql($sql)
	{
		$this->notify('before_load', array(&$this));
//		$this->data = $this->selectSql($sql, true);
		$this->setData($this->selectSql($sql, true));
		$this->notify('load', array(&$this));
	}
	function loadData($data)
	{
		$this->notify('before_load', array(&$this));
//		$this->data = $data;
		$this->setData($data);
		$this->notify('load', array(&$this));
	}

	private function setData(&$data)
	{
		$this->data = array();
		if (is_array($data) || (is_object($data) && $data instanceof IteratorAggregate))
		{
			foreach ($data as $row)
			{
				$item = new ResultSet();
				$item->init($this, $row);
				$this->data[] = $item;
				unset($item);
			}
		}
	}

	// relations
	/**
	 * Один-ко-многим или Многие-ко-многим
	 * $data -- массив с данными этой модели (который возвращает select(), 
	 * например)
	 * $info -- инфа о field
	 */
	function mapHasMany(&$data, $info, $one = false, $type = 'HasMany')
	{
		// один - ко - многим
		$field_name = $info['name'];
		$fk = $info[$type]['fk'];
		$pk = $info[$type]['pk'];
		$self_name = $info[$type]['name'];

		$model = $this->getInitModel($field_name);
		if (!isset($model)) return;

		if (!$one)
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
				$where = $model->quoteField($fk) .'='.$model->quote($v[$pk]);
				$model->load($where);

				$data[$k][$field_name] = $model;
			}
		}
		else
		{
				//дублирование (shit), надо убрать. как? (c) dz
				$where = $model->quoteField($fk) .'='.$model->quote($data[$pk]);
				$model->load($where);

				$data[$field_name] = $model;
		}
	}
	
	function mapManyToMany(&$data, $info, $one = false)
	{
		//терлим-бом-бом! (c) dz
		return $this->mapHasMany($data, $info, $one, 'ManyToMany');
	}

	//modified by dz. работающий вариант join'ов для has_one (@ 2008.02.04)
	function buildJoin($fields, &$fields_str)
	{
		$sql = '';
		$types = array('HasOne');

		foreach ($fields as $v)
		{
			$info = $this->_fields_info[$v];
			$type = $info['type'];
			if (in_array($type, $types) && !$this->isLazyLoadMode($info))
			{
				$field_name = $info['name'];
				$fk = $info[$type]['fk'];
				$pk = $info[$type]['pk'];
				$self_name = $info[$type]['name'];

				$f_model = $this->getInitModel($field_name);

				if ($type == "HasOne")
				{
					$where = "(" . $this->quoteField($fk)." = ".$f_model->quoteField($pk) . ")";
					if ($info["where"])
						$where .= " AND (" . $where . ")";
					if ($f_model->where)
						$where .= " AND (" . $f_model->where . ")";
					$sql .= 
					   ($info[$type]['only'] 
							? " INNER JOIN "
							: " LEFT JOIN "
						)
						. $this->buildTableNameAlias($f_model->table) 
						.	" ON ("
						.		  $where
						.	     ")"
						;
					$fields_str .= ", " . $this->buildJoinFieldAliases($f_model);
				}
			}
		}

		return $sql;
	}

	function mapHasOne(&$data, $info, $one = false)
	{
		$type = 'HasOne';
		$field_name = $info['name'];
		$fk = $info[$type]['fk'];
		$pk = $info[$type]['pk'];
		$self_name = $info[$type]['name'];

		$model = $this->getInitModel($field_name);
		if (!isset($model)) return;

		if (!$one)
		{
			foreach ($data as $k=>$v)
			{
				$where = $model->quoteField($pk) .'='.$model->quote($v[$fk]);
				$f_row_model = clone $model;
				$f_row_model->load($where);
				$item = $f_row_model;
				if ($item)
					$data[$k][$field_name] = $item;
			}
		}
		else
		{
			//дублирование (shit), надо убрать. как? (c) dz
			$where = $model->quoteField($pk) .'='.$model->quote($data[$fk]);
			$f_row_model = clone $model;
			$f_row_model->load($where);
			$item = $f_row_model;
			if ($item)
				$data[$field_name] = $item;
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
		//buildJoin может менять список загружаемых полей (by dz)
		$fields_str = $this->buildFieldAliases($this->fields);
		$joins_str = $this->buildJoin($this->foreign_fields, &$fields_str);
		$sql1 =  ' SELECT ' . $fields_str
			. ' FROM '   . $this->buildTableNameAlias($this->table)
			. $joins_str
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
//		$data = $this->rh->db->query($sql);
		$data = DBAL::getInstance()->query($sql);

//		echo $sql."<br /><br />";
		
		$this->loadForeignFields($data);

		return $data;
	}

	//dz: новый вариант. теперь поддерживает загрузку has_one через join (@2008.02.04)
	function loadForeignFields(&$data)
	{
		foreach ($this->foreign_fields as $v)
		{
			$info = $this->_fields_info[$v];
			if (isset($info['type']))
			{
				if (in_array($info["type"], array('HasOne')) && !$this->isLazyLoadMode($info))
				{
					$f_fields = array();
					$field_name = $info['name'];
					$f_model = $this->getInitModel($field_name);

					foreach ($f_model->fields as $field)
					{
						$name = $this->getQuotedJoinFieldAlias($field, $f_model);
						$f_fields[$field] = $this->unquoteName($name);
					}

					if (!empty($f_fields))
					{
						foreach ($data as &$_row)
						{
							$f_row = array();
							foreach ($f_fields as $orig_field => $res_field)
							{
								$f_row[$orig_field] = $_row[$res_field];
								unset($_row[$res_field]);
							}

							$f_row_model = clone $f_model;
							$f_row_model->loadData(array($f_row));
							$_row[$field_name] = $f_row_model;
							unset($f_row);
						}
					}
					unset($f_fields);
				}
				//lazy load now (c) dz
//				else
//				{
//					$method_name = 'map'.$info['type'];
//					$this->$method_name($data, $info);
//				}
			}
		}

	}

	//author dz. реализуем ленивую загрузку.
	function loadForeignField($field, &$data = null)
	{
//			$info = $this->foreign_fields[$field];
			$info = $this->_fields_info[$field];
			$method_name = 'map'.$info['type'];
			if (is_null($data))
				$this->$method_name($this->data, $info);
			else
				$this->$method_name($data, $info, true);
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

	//was renamed from "count" by dz @ 2008.02.04
	function get_count($where=NULL)
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
	function buildJoinFieldAliases($model)
	{
		$res = array();
		foreach ($model->fields as $field)
			$res[] = $this->quoteJoinFieldAlias($field, $model);
		$fields_sql = implode(',', $res);
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
		{
			if ($where && $where_sql)
				$where = " AND (" . $where . ")";
			$where_sql = ' WHERE ' . $where_sql . $where;
		}
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

	//dz: теперь конфиг для order лежить только в $this->order, не в $this->fields_info (@ 2008.02.04)
	function buildOrderBy($fields)
	{
		if (empty($fields))
			$orderby_sql = '';
		else
		{
			$orderby_sql = ' ORDER BY ';
			if (is_array($fields))
			{
				$orders = array();
				foreach ($fields as $field => $order)
				{
					if (is_int($field))
					{
						$field = $order;
						$order = "ASC";
					}
					$orders[] = $this->quoteOrderField($field, $order);
				}
				$orderby_sql .= implode(',', $orders);
			}
			else
				$orderby_sql .= $fields;
		}
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
	//author dz. нужно для join'ов
	function unquoteName($name)
	{
		return trim($name, '`');
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
	function quoteOrderField($name, $order)
	{
		return $this->_quoteField($name, $this->table) . " " . $order;
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

	//author dz: правильное именование полей приджойненных таблиц для вставки в select (@ 2008.02.04)
	function quoteJoinFieldAlias($name, $model)
	{
		$info =& $model->_fields_info[$name];
		if (!isset($info)) return NULL;

		return isset($info['alias']) 
			?  $info['source_full'].' AS '.$this->quoteName($model->table . "_" . $this->unquoteName($info['alias']))
			: $info['source_full'].' AS '.$this->quoteName($model->table . "_" . $this->unquoteName($info["source"]));
	}

	//author dz: правильно именование полей приджойненных таблиц для разбора результатов (@ 2008.02.04)
	function getQuotedJoinFieldAlias($name, $model)
	{
		$info =& $model->_fields_info[$name];
		if (!isset($info)) return NULL;

		return isset($info['alias']) 
			? $this->quoteName($model->table . "_" . $this->unquoteName($info['alias']))
			: $this->quoteName($model->table . "_" . $this->unquoteName($info["source"]));
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

	//author dz: $this->has_one, $this->has_many, $this->many_to_many
	function makeForeignsConfig()
	{
		$this->makeForeignConfig('has_one');
		$this->makeForeignConfig('has_many');
		$this->makeForeignConfig('many_to_many');
	}

	//type in 'has_one', 'has_many', 'many_to_many'
	function makeForeignConfig($type)
	{
		if (!isset($this->$type))
			return ;

		if (!is_array($this->$type))
			$this->$type = array($this->$type);

		foreach ($this->$type as $key => $config)
		{
			$res = array();

			$field = null;
			//получили поле
			if (!is_numeric($key))
				$field = $key;

			//получили имя класса
			if ($field && isset($this->$field))
				$className = get_class($this->$field);
			elseif (!is_array($config))
				$className = $config;
			elseif (isset($config["name"]))
			{
				if (Inflector::camelize($config["name"]) == $config["name"])
					$className = $config["name"];
				else
					Inflector::underscore($config["name"]);
			}
			elseif (isset($field))
				$className = Inflector::camelize($field);

			//сейчас уж точно получили поле
			if (!$field)
			{
				//1
				if ($type == 'has_one')
					$field = Inflector::many_singularize_for_underscope(Inflector::underscore($className));
//					$field = Inflector::underscore($className);
				else
					$field = Inflector::underscore($className);
			}

			//получили объект модели
			if (!isset($this->$field))
			{
				if (is_array($config) && isset($config["model"]) && is_obj($config["model"]))
					$this->$field = $config["model"];
				else
				{
					$this->rh->UseModel($className);
					$this->$field = new $className();
				}
			}

			//fk
			if (is_array($config) && isset($config["fk"]))
				$fk = $config["fk"];
			else
			{
				//2
				if ($type == "has_one")
					$fk = Inflector::many_singularize_for_underscope(Inflector::underscore($className)) . "_id";
//					$fk = Inflector::underscore($className) . "_id";
				elseif ($type == "has_many" || $type == "many_to_many")
					$fk = Inflector::many_singularize_for_underscope(Inflector::underscore(get_class($this))) . "_id";
			}

			//pk
			if (is_array($config) && isset($config["pk"]))
				$pk = $config["pk"];
			//3
			elseif (($type == "has_many" || $type == "has_many") && isset($this->pk))
				$pk = $this->pk;
			elseif ($type == "has_one" && isset($this->$field->pk))
				$pk = $this->$field->pk;
			else
				$pk = "id";

			$res = array();
			$res["name"] = $field;
			//4
			$res["type"] = $camel = Inflector::camelize($type);
			$res[$camel] = array("pk" => $pk, "fk" => $fk);
			$this->fields_info[] = $res;
			unset($res);
		}

	}

	//author dz: проверяем ленивый ли режим загрузки foreign'а
	function isLazyLoadMode($field_config)
	{
		if ("HasOne" == $field_config["type"])
			return (isset($field_config["lazy_load"]) ? $field_config["lazy_load"] : false);
		else
			return true;
	}

	//ленивая инициализация моделей. сначала храним не инициализированную модель. при обращении инитим. по-моему супер. (c) dz
	function getInitModel($field_name)
	{
		if (!isset($this->$field_name))
			return null;
		if (!$this->$field_name->is_initialized)
			$this->$field_name->initialize($this->rh);

		return $this->$field_name;
	}

	function isForeignField($field)
	{
		return in_array($field, $this->foreign_fields);
	}

	public function &getData()
	{
		return $this->data;
	}

	/*
	 * Author dz
	 * реализация интерфейсов IteratorAggregate, ArrayAccess, Countable
	 *
	 */

	//implements IteratorAggregate
	public function getIterator() 
	{
		return new ArrayIterator($this->data); 
	}

	//implements ArrayAccess
	public function offsetExists($key) { return isset($this->data[$key]); }
	
	public function offsetGet($key)
	{ 
		if (isset($this->data[$key]))
			return $this->data[$key]; 
		elseif (in_array($key, $this->foreign_fields))
		{
			$this->loadForeignField($key);
			return $this->data[$key];
		}
	}

	public function offsetSet($key, $value) { $this->data[$key] = $value; }

	public function offsetUnset($key) { unset($this->data[$key]); }

	//implements Countable
	public function count() { return (!empty($this->data)) ? count($this->data) : 0; }

	public function __toString() 
	{
		$res = "<br />object of " . get_class($this) . " values:";
		foreach ($this->data as $k=>$item)
		{
			$res .= "<br />" . $k . " => ";
			if (is_object($item))
				$res .= $item->__toString();
			else
			{
				if (strlen($item) > 255)
					$item = substr(htmlentities($item), 0, 255) . "<font color='green'>&hellip;</font>";
				$res .= $item;
			}
		}

		return $res;
	}
}  

?>