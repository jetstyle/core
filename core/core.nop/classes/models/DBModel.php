<?php

//интерфейс получения даных от объекта. используется в Controller::add_config, ну и вообще везде где надо определить косит ли объект под массив. (с) dz
interface DataContainer
{
	public function &getData();
}

/**
 * Класс DBModel - базовый класс моделек, хранящих чего-то в БД
 * 
 */
$this->useClass('models/Model');
$this->useClass('models/ResultSet');
$this->useClass('DBQueryParser');
$this->useClass("Inflector");

class DBModel extends Model implements IteratorAggregate, ArrayAccess, Countable, DataContainer
{
	/**
	 * Имя таблицы
	 *
	 * @var string
	 **/
	protected $table = NULL;
	
	/**
	 * Алиас таблицы
	 *
	 * @var string
	 **/
	protected $tableAlias = NULL;
	
	/**
	 * Алиасы таблиц, которые нельзя использовать
	 *
	 * @var array
	 **/
	protected $bannedTableAliases = array();
	
	/**
	 * Массив полей, заданный пользователем
	 * Эти поля парсятся и превращаются в $tableFields и $foreignFields
	 *
	 * ex.:
	 * $fields = array(
	 * 		'id',
	 * 		
	 * 		// alias => source
	 * 		'title' => 'title_pre', 
	 * 		'date' => 'DATE_FORMAT(inserted, "%d.%m.%Y")',
	 * 		
	 * 		// has_one
	 * 		'>rubric' => array(
	 * 			'model' => 'RubricsModel',
	 * 			'pk' => 'rubric_id',
	 * 			'fk' => 'id',				// optional
	 * 		),
	 * 		'has_one:rubric' => array(
	 * 			'model' => 'RubricsModel',
	 * 			'pk' => 'rubric_id',
	 * 			'lazy_load' => true,		// optional
	 * 		), 
	 * 
	 * 		// has_many
	 * 		'>>cities' => array(
	 * 			'model' => 'CitiesModel',
	 * 			'pk' => 'id',				// optional
	 * 			'fk' => 'city_id',			
	 * 		),
	 * 		'has_many:cities' => array(
	 * 			'model' => 'CitiesModel',
	 * 			'pk' => 'id',				// optional
	 * 			'fk' => 'city_id',			
	 * 		),
	 * 
	 * 		// many2many
	 * 		'<>users' => array(
	 *			'model' => 'TestUsersModel',
	 *			'through' => array(
	 *				'table' => 'test2users',
	 *				'pk' => 'test_id',
	 *				'fk' => 'user_id',
	 *			),
	 *		),
	 * 		'many2many:users' => array(
	 *			'model' => 'TestUsersModel',
	 *			'through' => array(
	 *				'table' => 'test2users',
	 *				'pk' => 'test_id',
	 *				'fk' => 'user_id',
	 *			),
	 *		)
	 * );
	 * 
	 * @var array
	 **/
	protected $fields = array('*');
	
	/**
	 * Поля таблицы
	 *
	 * @var array
	 **/
	protected $tableFields = array();
	
	/**
	 * Внешние поля 
	 *
	 * @var array
	 **/
	protected $foreignFields = array();
	
	/**
	 * Массив объектов моделей для внешних ключей
	 *
	 * @var array
	 **/
	protected $foreignModels = array();
	
	/**
	 * Условие where запроса
	 *
	 * ex.:
	 * $where = '{id} = 1 AND {_state} = 0';
	 * 
	 * @var string
	 **/
	public $where = '';
	
	/**
	 * параметры GROUP BY запроса
	 *
	 * @var string
	 **/
	protected $group = NULL;
	
	/**
	 * параметры ORDER BY запроса
	 *
	 * @var array
	 **/
	protected $order = array();
	
	/**
	 * параметр LIMIT запроса
	 *
	 * @var int
	 **/
	protected $limit = NULL;
	
	/**
	 * параметр offcet запроса
	 *
	 * @var int
	 **/
	protected $offset = NULL;
		
	// protected $isInitialized = false;

	protected $sqlParts = array();

	protected $data = NULL;


	protected function initialize()
	{
		//$this->is_initialized = true; //иногда создаем объект, а потом делаем "initialize"

		$parent_status = parent::initialize();

		if (is_null($this->table))
		{
			$this->table = $this->autoDefineTable();
		}

		$this->addFields($this->fields);

		//var_dump($this->tableFields);
		//var_dump($this->foreignFields);
		
		return $parent_status && True;
	}
	
	public function getTableName()
	{
		return $this->table;
	}
	
	public function getTableAlias()
	{
		return $this->tableAlias ? $this->tableAlias : $this->table;
	}
	
	public function setTableAlias($v)
	{
		$this->tableAlias = $v;
	}
	
	public function setBannedTableAliases(&$v)
	{
		$this->bannedTableAliases = &$v;
		$this->updateTableAlias();
	}
	
	public function setLimit($v)
	{
		if (is_numeric($v))
		{
			$this->limit = $v;
		}
	}
	
	public function setOrder($v)
	{
		$this->order = $v;
	}
	
	public function setGroupBy($v)
	{
		$this->group = $v;
	}
	
	public function setGroup($v)
	{
		return $this->setGroupBy($v);
	}
	
	public function getForeignFieldConf($fieldName)
	{
		return $this->foreignFields[$fieldName];
	}
	
	/**
	 * Clear fields
	 *
	 * @return void
	 **/
	public function clearFields()
	{
		$this->fields = array();
		$this->foreignFields = array();
		$this->foreignModels = array();
		$this->bannedTableAliases = array();
		$this->tableFields = array();
	}
	
	/**
	 * Remove field 
	 *
	 * @param string $fieldName (ex.: id, title, >rubric, rubric, etc.)
	 */
	public function removeField($fieldName)
	{
		if (strpos($fieldName, ':') !== false)
		{
			$fieldNameParts = explode(':', $fieldName);
			$fieldName = $fieldNameParts[1];
		}
		else
		{
			// many to many
			if (substr($fieldName, 0, 2) == '<>')
			{
				$fieldName = substr($fieldName, 2);
			}
			// one to many
			elseif(substr($fieldName, 0, 2) == '>>')
			{
				$fieldName = substr($fieldName, 2);
			}
			// one to one
			elseif(substr($fieldName, 0, 1) == '>')
			{
				$fieldName = substr($fieldName, 1);
			}
		}
		
		if (isset($this->tableFields[$fieldName]))
		{
			unset($this->tableFields[$fieldName]);
		}
		else
		{
			if (isset($this->foreignFields[$fieldName]))
			{
				unset($this->foreignFields[$fieldName]);
			}
			
			if (isset($this->foreignModels[$fieldName]))
			{
				unset($this->foreignModels[$fieldName]);
			}
		}
	}
	
	public function addFields($fields)
	{
		if (!is_array($fields) || empty($fields)) return;
		
		foreach ($fields AS $key => $value)
		{			
			if (is_numeric($key))
			{
				$this->addField($value);
			}
			else
			{
				$this->addField($key, $value);
			}
		}
	}
	
	public function addField($fieldName, $config = NULL)
	{
		
		if (NULL === $config)
		{
			$this->tableFields[$fieldName] = array(
				'name' => $fieldName
			);
		}
		else
		{
			// ex: has_one:rubric, has_many:rubrics, many2many:rubrics 
			if (strpos($fieldName, ':') !== false)
			{
				$fieldNameParts = explode(':', $fieldName);
				$this->addForeignField($fieldNameParts[0], $fieldNameParts[1], $config);
			}
			else
			{
				// many to many
				if (substr($fieldName, 0, 2) == '<>')
				{
					$this->addForeignField('many2many', substr($fieldName, 2), $config);
				}
				// one to many
				elseif(substr($fieldName, 0, 2) == '>>')
				{
					$this->addForeignField('has_many', substr($fieldName, 2), $config);
				}
				// one to one
				elseif(substr($fieldName, 0, 1) == '>')
				{
					$this->addForeignField('has_one', substr($fieldName, 1), $config);
				}
				// table field
				else
				{
					if (is_array($config))
					{
						if ($config['type'])
						{
							$this->foreignFields[$fieldName] = $config;
						}
						else
						{
							$this->tableFields[$fieldName] = $config;
							$this->tableFields[$fieldName]['name'] = $fieldName;
						}
					}
					else
					{
						$this->tableFields[$fieldName] = array(
							'name' => $fieldName,
							'source' => $config
						);
					}
				}
			}
		}
	}
	
	public function getCount($where = NULL)
	{
		$sqlParts = $this->getSqlParts($where);
		$sql = '
			SELECT COUNT(*) AS total
			'.$sqlParts['from'].'
			'.$sqlParts['join'].'
			'.$sqlParts['where'].'
		';
		
		$result = $this->rh->db->queryOne($sql);
		return intval($result['total']);
	}
	
	/**
	 * Return primary key 
	 *
	 * @return string
	 */
	public function getPk()
	{
		if ($this->pk)
		{
			return $this->pk;
		}
		else
		{
			return 'id';
		}
	}
	
	public function setData($data)
	{
		if (is_array($data))
		{
			$this->data = array();
			foreach ($data AS $row)
			{
				$item = new ResultSet();
				$item->init($this, $row);

				$this->data[] = $item;
				unset($item);
			}
		}
		else
		{	
			$this->data = null;
		}
	}
	
	// ########## QUOTES ############## //
	
	public function quote($str)
	{
		return $this->dbQuote($str);
	}
	
	public function dbQuote($str)
	{
		return $this->rh->db->quote($str);
	}
	
	public function quoteValue($value)
	{
		return (isset($value) ?  $this->dbQuote($value) : 'NULL');
	}
	
	/**
	 * wrap string with `
	 *
	 * @param string $name
	 * @return string
	 **/
	public function quoteName($name)
	{
		$result = '';
		if ($name !== '*') 
		{
			$result = '`'.str_replace('`', '``', $name).'`';
		}
		else 
		{	
			$result = $name;
		}
		return $result;
	}
	
	/**
	 * Construct `table`.`field`
	 *
	 * @return string
	 **/
	public function quoteField($name)
	{
		return $this->quoteName($this->getTableAlias()).'.'.$this->quoteName($name);
	}
	
	// ########## END QUOTES ############## //
	
	
	
	protected function updateTableAlias()
	{
		$ta = $this->getTableAlias();
		$i = 2;
		while (in_array($this->getTableAlias(), $this->bannedTableAliases))
		{
			$this->setTableAlias($ta.$i++);
		}
	}
	
	protected function addForeignField($type, $fieldName, $config = NULL)
	{
		if (!in_array($type, array('has_one', 'has_many', 'many2many')))
		{
			return;
		}
		
		// get model name
		if (isset($this->foreignModels[$fieldName]))
		{
			$className = get_class($this->foreignModels[$fieldName]);
		}
		elseif (!is_array($config))
		{
			$className = $config;
			$config = array();
		}
		elseif (isset($config["model"]))
		{
			$className = $config["model"];
			unset($config["model"]);
		}

		$config["name"] = $fieldName;
		$config["type"] = $type;
		$config['className'] = $className;
		
		$this->foreignFields[$fieldName] = $config;	
	}
	
	
	/**
	 * Try to determine table name from class name
	 *
	 * @return string
	 */
	protected function autoDefineTable()
	{
		$className = get_class($this);
		if ($className == 'DBModel')
		{
			return NULL;
		}
		return Inflector::underscore(str_replace(array("Model", "Basic"), "", $className));
	}

	public function load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->notify('before_load', array(&$this));

		$this->setData($this->select($where, $limit, $offset, true));
		
		$this->notify('load', array(&$this));
	}
	
	protected function loadSql($sql)
	{
		$this->notify('before_load', array(&$this));
//		$this->data = $this->selectSql($sql, true);
		$this->setData($this->selectSql($sql, true));
		$this->notify('load', array(&$this));
	}
	
	/**
	 * один - ко - многим
	 *
	 * @return void
	 **/
	protected function mapHasMany($fieldName, &$data)
	{
		$fieldinfo = &$this->foreignFields[$fieldName];
		$model = &$this->getForeignModel($fieldName);
		
		if (!isset($model)) return;

		$where = $model->quoteField($fieldinfo['fk']) .'='. $model->dbQuote($data[$fieldinfo['pk']]);
		$model->load($where);

		$data[$fieldName] = &$model;
	}
	
	/**
	 * многие ко многим
	 * 
	 **/
	protected function mapMany2Many($fieldName, &$data)
	{
		$fieldinfo = &$this->foreignFields[$fieldName];
		$model = &$this->getForeignModel($fieldName);
		
		if (!isset($model)) return;
		
		$qt = $this->quoteName($this->rh->db_prefix.$fieldinfo['through']['table']);
		
		$sqlParts = $model->getSqlParts();
		$sqlParts['join'] .= ' 
			INNER JOIN '.$qt.' AS '.$qt.' 
			ON 
			(
				('.$model->quoteField($fieldinfo['fk']).'='.$qt.'.'.$this->quoteName($fieldinfo['through']['fk']).')
				 AND
				('.$qt.'.'.$this->quoteName($fieldinfo['through']['pk']) .'='. $model->dbQuote($data[$fieldinfo['pk']]).') 
			) ';
				
		$model->loadSql(implode(' ', $sqlParts));
		$data[$fieldName] = &$model;		
	}

	function mapHasOne($fieldName, &$data)
	{
		$fieldinfo = &$this->foreignFields[$fieldName];
		$model = &$this->getForeignModel($fieldName);
		
		if (!isset($model)) return;

		$where = $model->quoteField($fieldinfo['fk']) .'='. $model->dbQuote($data[$fieldinfo['pk']]);
		$model->load($where);

		$data[$fieldName] = &$model;
	}
	
	
	/**
	 * Загрузить данные о файлах из аплоада
	 *
	 **/
	protected function mapUpload(&$data, $info)
	{	
		$model =& $this->rh->upload;
		if (!isset($model)) return;

		$fname = str_replace('*', $data['id'], $info['path']);

		$file = $model->getFile($fname);
		if ($file) 
		{
			list($width, $height, $type, $attr) = getimagesize($file->name_full);
			$file->height = $height;
			$file->width = $width;
		}
		
		$data[$info['name']] = $file;
	}

	/**
	 * Строит join для has_one
	 *
	 * @return string
	 **/
	protected function buildJoin(&$fields)
	{
		$joinSql = '';
		$fieldsSql = '';
		$whereSql = '';
		
		$allowedTypes = array('has_one');

		foreach ($fields AS &$v)
		{
			if (!in_array($v['type'], $allowedTypes) || (isset($v['lazy_load']) && $v['lazy_load']))
			{
				continue;
			}

			$foreignModel = &$this->getForeignModel($v['name']);

			$where = "(" . $this->quoteField($v['pk'])." = ".$foreignModel->quoteField($v['fk']) . ")";
			
			if ($v["where"])
			{
				$where .= " AND (" . $foreignModel->parse($v["where"]) . ")";
			}
			
			
			if ($foreignModel->where)
			{
				$whereSql .= ($whereSql ? " AND " : "")." (" . $foreignModel->parse($foreignModel->where) . ")";
			}
			
			
			$joinSql .= 
			   (($v['join'] == 'inner') 
					? " INNER JOIN "
					: " LEFT JOIN "
				)
				. $foreignModel->getTableNameAlias() 
				.	" ON ("
				.		  $where
				.	     ")"
				;
				
			$fieldsSql .= "," . $foreignModel->getFieldsForJoin();
		}

		return array($joinSql, $fieldsSql, $whereSql);
	}
	
	public function getSqlParts($where=NULL, $limit=NULL, $offset=NULL)
	{
		if (!empty($this->sqlParts))
		{
			return $this->sqlParts;
		}
		
		$this->sqlParts = array();
				
		$this->sqlParts['fields'] = 'SELECT '.$this->getFields($this->tableFields);
		$this->sqlParts['from'] = 'FROM '.$this->getTableNameAlias();
		
		list($joinSql, $joinFields, $joinWhere) = $this->buildJoin($this->foreignFields);
		if ($joinWhere)
		{
			if ($where)
			{
				$where .= ' AND '.$joinWhere;
			}
			else
			{
				$where = $joinWhere;
			}
		}
		
		$this->sqlParts['join'] = $joinSql;
		$this->sqlParts['where'] = $this->buildWhere($where);
		$this->sqlParts['group'] = $this->buildGroupBy($this->group);
		$this->sqlParts['order'] = $this->buildOrderBy($this->order);
		$this->sqlParts['limit'] = $this->buildLimit($limit, $offset);
		
		$this->sqlParts['fields'] .= $joinFields;
		
//		var_dump($this->sqlParts);
		
		return $this->sqlParts;
	}
		
	protected function getFields()
	{
		return implode(',', array_map(array(&$this, 'buildFieldAlias'), $this->tableFields));
	}

	public function getFieldsForJoin()
	{
		return implode(',', array_map(array(&$this, 'buildJoinFieldAlias'), $this->tableFields));
	}

	public function selectSql($sql, $isLoad=false)
	{
//		var_dump($sql);
//echo $sql;
		
		$data = DBAL::getInstance()->query($sql);
		
		$this->fillForeignModelsWithData($data);
		return $data;
	}

	protected function fillForeignModelsWithData(&$data)
	{
		if (!is_array($this->foreignFields) || empty($this->foreignFields))
		{
			return;
		}
	
		// 
		foreach ($data AS $row => &$d)
		{
			$foreignData = array();
			foreach($d AS $fieldName => $fieldValue)
			{
				if (!isset($this->tableFields[$fieldName]))
				{
					$fieldParts = explode(':', $fieldName);
					if (!is_array($foreignData[$fieldParts[0]]))
					{
						$foreignData[$fieldParts[0]] = array();
					}
					$foreignData[$fieldParts[0]][$fieldParts[1]] = $fieldValue;
					unset($d[$fieldName]);
				}
			}
			
			if (empty($foreignData))
			{
				return;	
			}
			
			foreach ($this->foreignFields AS $foreignField)
			{
				if ($foreignField['type'] == 'has_one' && !$foreignField['lazy_load'])
				{
					$model = &$this->foreignModels[$foreignField['name']];
					$tableAlias = $model->getTableAlias();
					
					if (isset($foreignData[$tableAlias]))
					{
						$clonedModel = clone $model;
						$clonedModel->setData(array($foreignData[$tableAlias]));
						$d[$foreignField['name']] = $clonedModel;
					}
				}
			}
		}
	}

	public function loadForeignField($fieldName, &$data)
	{
		$methodName = 'map'.Inflector::camelize($this->foreignFields[$fieldName]['type']);
		$this->$methodName($fieldName, $data);
	}

	protected function select($where=NULL, $limit=NULL, $offset=NULL)
	{
		$sqlParts = $this->getSqlParts($where, $limit, $offset);
		
		return $this->selectSql(implode(' ', $sqlParts));
	}
	
	protected function onBeforeInsert(&$row)
	{
		if (array_key_exists('_created', $this->_fields_info) 
			&& !array_key_exists('_created', $row))
				$row['_created'] = date('Y-m-d H:i:s');
	}
	
	protected function onBeforeUpdate(&$row)
	{
		if (array_key_exists('_modified', $this->_fields_info) 
			&& !array_key_exists('_modified', $row))
				$row['_modified'] = date('Y-m-d H:i:s');
	}
	
	/*
	protected function insert(&$row)
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
			.$this->buildWhere($where_sql);
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
	*/
	
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

	protected function buildFieldAlias($field)
	{
		$result = '';
				
		if ($field['source'])
		{
			if (preg_match('/^[\d\w_]+$/i', $field['source']))
			{
				$result .= $this->quoteField($field['source']);
			}
			else
			{
				$result .= $this->parse($field['source']);
			}
			$result .= ' AS '.$this->quoteName($field['name']);
		}
		else
		{
			$result .= $this->quoteField($field['name']);
			
			//$this->getTableName().'.'.$this->quoteName($field['name']);
		}
		
		return $result;
	}
	
	protected function buildJoinFieldAlias($field)
	{
		$result = '';
				
		if ($field['source'])
		{
			if (preg_match('/^[\d\w_]+$/i', $field['source']))
			{
				$result .= $this->quoteField($field['source']);
			}
			else
			{
				$result .= $this->parse($field['source']);
			}
		}
		else
		{
			$result .= $this->quoteField($field['name']);
		}
		$result .= ' AS '.$this->quoteName($this->getTableAlias().':'.$field['name']);
		
		return $result;
	}
	
	public function getTableNameAlias()
	{
		return $this->quoteName($this->rh->db_prefix.$this->getTableName()) .' AS '.$this->quoteName($this->getTableAlias());
	}
	
	protected function buildWhere($where)
	{
		if (isset($this->where))
		{ 
			$where_sql = $this->where;
		}
		else
		{
			$where_sql = '';
		}

		if ($where || $where_sql)
		{
			if ($where && $where_sql)
			{
				$where = " AND (" . $where . ")";
			}
			$where_sql = ' WHERE ' . $where_sql . $where;
		}
		else
		{
			$where_sql = '';
		}
		return $this->parse($where_sql);
	}
	
	protected function buildGroupBy($fields)
	{
		if (empty($fields))
			$sql = '';
		else
		{
			$sql = ' GROUP BY '. (
				is_array($fields)
				? implode(',',array_map(array(&$this, 'quoteField'), $fields))
				:	$this->quoteField($fields))
				;
			$sql .= $this->buildHaving();
		}
		return $sql;
	}

	protected function buildHaving()
	{
		if ($this->having)
			return " HAVING " . $this->having;
	}

	protected function buildOrderBy($fields)
	{
		if (empty($fields))
		{
			$sql = '';
		}
		else
		{
			$sql = ' ORDER BY ';
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
					$orders[] = $this->quoteField($field) . " " . $order;
				}
				$sql .= implode(',', $orders);
			}
			else
			{
				$sql .= $fields;
			}
		}
		return $sql;
	}
	
		
	/**
	 * Parse string and quote fields
	 *
	 * ex.:
	 * '{id} = 1 AND {_state} = 0' wiil become `tableAlias`.`id` = 1 AND `tableAlias`.`_state` = 0
	 * 
	 * @param string $str
	 * @return string
	 */
	protected function parse($str)
	{
		return preg_replace_callback('#{([^}]+)}#', array(&$this, 'parseCallback'), $str);
	}
	
	protected function parseCallback($matches)
	{
		return $this->quoteField($matches[1]);
	}

	public function &getForeignModel($fieldName)
	{
		if (!isset($this->foreignModels[$fieldName]))
		{
			$this->initForeignModel($fieldName);
		}
		return $this->foreignModels[$fieldName];
	}
	
	protected function initForeignModel($fieldName)
	{
		if (!isset($this->foreignFields[$fieldName]))
		{
			$this->foreignModels[$fieldName] = NULL;
			return;
		}
		
		$field = &$this->foreignFields[$fieldName];
		$this->rh->useModel($field['className']);
		$model = new $field['className']($this->rh);
		
		$model->setBannedTableAliases($this->bannedTableAliases);
		$this->bannedTableAliases[] = $model->getTableAlias();

		$this->foreignModels[$fieldName] = &$model;

		//fk
		if (!isset($field["fk"]))
		{
			if ($field['type'] == "has_one" || $field['type'] == "many2many")
			{
				$field["fk"] = $this->foreignModels[$fieldName]->getPk();
			}
			else
			{
				$field["fk"] = Inflector::many_singularize_for_underscope(Inflector::underscore(get_class($this))) . "_id";
			}
		}

		//pk
		if (!isset($field["pk"]))
		{
			if ($field['type'] == "has_one")
			{
				$field["pk"] = Inflector::many_singularize_for_underscope(Inflector::underscore($field['className'])) . "_id";
			}
			else
			{
				$field["pk"] = $this->getPk();
			}
		}
		
		if (isset($field['order']) && is_array($field['order']))
		{
			$model->setOrder($field['order']);
		}
		
		if (isset($field['where']))
		{
			if ($model->where)
			{
				$model->where .= ' AND ';
			}
			
			$model->where .= $field['where']; 
		}
	}

	public function isForeignField($field)
	{
		if (isset($this->foreignFields[$field]))
			return true;
		else
			return false;
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

	public function haveData() 
	{ 
		return is_array($this->data); 
	}

	//implements IteratorAggregate
	public function getIterator() 
	{
		return new ArrayIterator($this->data); 
	}

	//implements ArrayAccess
	public function offsetExists($key) 
	{ 
		return $this->haveData() && isset($this->data[$key]); 
	}
	
	public function offsetGet($key)
	{ 
		if (!$this->haveData())
		{
			return null;
		}
		
		if (isset($this->data[$key]))
		{
			return $this->data[$key]; 
		}
		else
		{
			return null;	
		}
		/*
		elseif ($this->isForeignField($key))
		{
			$this->loadForeignField($key);
			return $this->data[$key];
		}
		*/
	}

	public function offsetSet($key, $value) 
	{
		if (NULL === $key)
		{
			$this->data[] = $value; 
		}
		else
		{
			$this->data[$key] = $value; 
		}
	}

	public function offsetUnset($key) 
	{ 
		unset($this->data[$key]); 
	}

	//implements Countable
	public function count() 
	{ 
		return ($this->haveData() && !empty($this->data)) ? count($this->data) : 0; 
	}

	public function __toString() 
	{
		$res = "<br />object of " . get_class($this) . " values:";
		if ($this->haveData())
		{
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
		}

		return $res;
	}
	
	/*
	public function setTable($value)
	{
		$this->table = $value;
	}
	
	public function setFields($fields)
	{
		if (!is_array($fields))
		{
			return false;
		}
		
		$this->fields = array();
		foreach ($fields AS $field)
		{
			$this->addField($field);
		}
	}
	*/
	/*
	public function addField($fieldName)
	{
		$this->fields[] = $fieldName;
	}
	*/
}  

?>