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
Finder::useClass('models/Model');
Finder::useClass('models/ResultSet');
Finder::useClass("Inflector");

class DBModel extends Model implements IteratorAggregate, ArrayAccess, Countable, DataContainer
{
	protected $db = null;

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
	protected $fields = array();

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


	protected $pagerEnabled = false;
	protected $pager = NULL;
	protected $pagerPerPage = 10;
	protected $pagerFrameSize = 9;
	protected $pagerVar = 'p';

	protected $sqlParts = array();

	protected $data = NULL;

	/**
	 * В некоторых случаях (например при удалении или апдейте) нам нужно использовать имя таблицы как префикс
	 *
	 * @var boolean
	 */
	protected $usePrefixedTableAsAlias = false;

	/**
	 * Ассоциативный масиив алиас таблицы => имя поля
	 *
	 * @var array
	 */
	protected $foreignAlias2FieldName = array();

	/**
	 * Модель содержит одну единственную запись, загруженную через метод self::loadOne()
	 * Данные доступны через model[field] вместо model[0][field]
	 *
	 * @var boolean
	 */
	protected $one = false;

	public static function factory($className = '')
	{
		$obj = null;
		$parts = explode(":", $className);

		if (count($parts)>1)
		{
			$className= $parts[0];
			$fieldSet = $parts[1];
		}

		if ($className)
		{
			if (substr($className, -5) != 'Model')
			{
				$className .= 'Model';
			}
		}
		else
		{
			$className = get_class(self);
		}

		//есть php-класс модели
		$classFile = Finder::findScript('classes/models', $className);
		if ( $classFile )
		{
			Finder::useModel($className);
			$model = new $className($fieldSet);
		}
		else
		{
			$model = new DBModel();
			if (!$model->loadConfig( $className, $fieldSet ))
			{
				throw new JSException('DBModel: can\'t find model "'.$className.'"'. ($fieldSet ? " with field set \"".$fieldSet."\"" : ""));
			}
		}

		return $model;
	}

	public static function quote($str)
	{
		return Locator::get('db')->quote($str);
	}

	public function __construct($fieldSet = null)
	{
		$this->db = &Locator::get('db');
		$this->initialize($fieldSet);
	}

	/**
	 * loadConfig
	 *
	 * загрузка файла с конфигом
	 *
	 * @param string $className		имя файла конфига из каталога classes/models
	 * @param string $fieldSet		имя набора полей из конфига
	 * @return boolean
	 */
	public function loadConfig( $fileName, $fieldSet = null )
	{
		$ymlFile  = Finder::findScript('classes/models', $fileName, 0, 1, 'yml') ;

		if ( $ymlFile )
		{
			$ymlConfig = YamlWrapper::load($ymlFile);

			if (!is_array($ymlConfig) || empty($ymlConfig))
			{
				return false;
			}

			if ( $fieldSet )
			{
				if (!isset( $ymlConfig[ $fieldSet ] ) || !is_array( $ymlConfig[ $fieldSet ] ) || empty( $ymlConfig[ $fieldSet ] ))
				{
					return false;
				}

				$ymlConfig = $ymlConfig[ $fieldSet ];
			}
			else if ( isset( $ymlConfig['default'] ) )
			{
				$ymlConfig = $ymlConfig['default'];
			}

			$this->setConfig($ymlConfig, $fileName);
			return true;
		}

		return false;
	}

	/**
	 * setConfig
	 *
	 * установка полей конфига в модель
	 *
	 * @param array $ymlConfig конфиг загруженный функцией loadConfig
	 */
	private function setConfig( $ymlConfig, $className = '' )
	{
		$this->setFields($ymlConfig['fields']);
		$this->setWhere($ymlConfig['where']);
		$this->setOrder($ymlConfig['order']);
		$this->setGroupBy($ymlConfig['group']);
		$this->setHaving($ymlConfig['having']);
		$this->setLimit($ymlConfig['limit']);

		if ( !empty( $ymlConfig['table'] ) )
		{
			$this->setTable( $ymlConfig['table'] );
		}
		else if ($className)
		{
			$this->autoDefineTable($className);
		}
	}

	// ######## GETTERS ############## //

	/**
	 * Return table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table;
	}

	/**
	 * Return table alias
	 *
	 * @return string
	 */
	public function getTableAlias()
	{
		if ($this->usePrefixedTableAsAlias)
		{
			return DBAL::$prefix.$this->table;
		}
		else
		{
			return $this->tableAlias ? $this->tableAlias : $this->table;
		}
	}

	/**
	 * Return table name with alias
	 *
	 * ex. `quickstart_test` AS `quickstart_test`
	 *
	 * @return string
	 */
	public function getTableNameWithAlias()
	{
		return $this->quoteName(DBAL::$prefix.$this->getTableName()) .' AS '.$this->quoteName($this->getTableAlias());
	}

	/**
	 * Return array of pages from pager object
	 *
	 * @return array
	 */
	public function getPages()
	{
		if ($this->pagerEnabled && $this->pager)
		{
			return $this->pager->getPages();
		}
		else
		{
			return null;
		}
	}

	/**
	 * Return config for foreign field
	 *
	 * @param string $fieldName
	 * @return array
	 */
	public function getForeignFieldConf($fieldName)
	{
		return $this->foreignFields[$fieldName];
	}

	/**
	 * Return data
	 *
	 * data is array of resultSet objects
	 *
	 * @return array
	 */
	public function &getData()
	{
		if ($this->one && is_array($this->data))
		{
			return $this->data[0];
		}
		else
		{
			return $this->data;
		}
	}

	public function getArray()
	{
		$result = array();

		if (is_array($this->data))
		{
			if ($this->one)
			{
				$result = $this->data[0]->getArray();
			}
			else
			{
				foreach ($this->data AS $k => $r)
				{
					$result[$k] = $r->getArray();
				}
			}
		}
		return $result;
	}

	public function getCount($where = NULL)
	{
		$sqlParts = $this->getSqlParts($where);
		$sql = '
			SELECT COUNT(*) AS total
			'.$sqlParts['from'].'
			'.$sqlParts['join'].'
			'.$sqlParts['where'].'
			'.$sqlParts['group'].'
		';

		if ($sqlParts['group'])
		{
			$total = $this->db->getNumRows($this->db->execute($sql));
		}
		else
		{
			$result = $this->db->queryOne($sql);
			$total = $result['total'];
		}

		return intval($total);
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


	// ######## SETTERS ############## //

	/**
	 * Set table alias
	 *
	 * @param string $v
	 * @return self
	 */
	public function setTableAlias($v)
	{
		$this->tableAlias = $v;
		return $this;
	}

	/**
	 * Set table name
	 *
	 * @param string $v
	 * @return self
	 */
	public function setTable($v)
	{
		$this->table = $v;
		return $this;
	}

	/**
	 * Set banned table alises
	 *
	 * @param array $v
	 * @return self
	 */
	public function setBannedTableAliases(&$v)
	{
		if (!is_array($v)) $v = array($v);
		$this->bannedTableAliases = &$v;
		$this->updateTableAlias();
		$this->bannedTableAliases[] = $this->getTableAlias();

		if (is_array($this->foreignModels) && !empty($this->foreignModels))
		{
			foreach ($this->foreignModels AS &$model)
			{
				$model->setBannedTableAliases($this->bannedTableAliases);
			}
		}
		return $this;
	}

	/**
	 * setWhere
	 *
	 * sets where condition
	 *
	 * @param string $where
	 */
	public function setWhere($where)
	{
		if (!empty($where))
		$this->where = $where;

		return $this;
	}

	/**
	 * Set query limit
	 *
	 * @param int $v
	 * @return self
	 */
	public function setLimit($v)
	{
		if (is_numeric($v))
		{
			$this->limit = $v;
		}
		return $this;
	}

	/**
	 * Set query order
	 *
	 * @param array $v
	 * @return self
	 */
	public function setOrder($v)
	{
		$this->order = $v;
		return $this;
	}

	/**
	 * Set query group by
	 *
	 * @param string $v
	 * @return self
	 */
	public function setGroupBy($v)
	{
		$this->group = $v;
		return $this;
	}

	/**
	 * Set query having
	 *
	 * @param string $v
	 * @return self
	 */
	public function setHaving($v)
	{
		$this->having = $v;
		return $this;
	}

	//	public function setGroup($v)
	//	{
	//		return $this->setGroupBy($v);
	//		if ($this->fromFactory) return $this;
	//	}


	public function setOne($v)
	{
		$this->one = $v;
	}


	/**
	 * Set custom pager object. Object must implement PagerInterface
	 *
	 * @param object $obj
	 * @return self
	 */
	public function setPager(&$obj)
	{
		if (is_object($obj) && in_array('PagerInterface', class_implements($obj)))
		{
			$this->pager = &$obj;
		}
		else
		{
			throw new Exception('Pager object must implement \'PagerInterface\'');
		}
		return $this;
	}

	public function setPagerPerPage($v)
	{
		if (is_numeric($v) && $v > 0)
		{
			$this->pagerPerPage = $v;
		}
		return $this;
	}

	public function setPagerFrameSize($v)
	{
		if (is_numeric($v) && $v > 0)
		{
			$this->pagerFrameSize = $v;
		}
		return $this;
	}

	public function setPagerVar($v)
	{
		$this->pagerVar = $v;
		return $this;
	}

	public function enablePager()
	{
		$this->pagerEnabled = true;
		return $this;
	}

	public function disablePager()
	{
		$this->pagerEnabled = false;
		return $this;
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

		return $this;
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
		return $this;
	}

	public function setFields($fields)
	{
		$this->clearFields();
		return $this->addFields($fields);
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

		return $this;
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
		return $this;
	}

	public function addForeignModel($fieldName, &$model)
	{
		$this->foreignModels[$fieldName] = &$model;

		$model->setBannedTableAliases($this->bannedTableAliases);
		//		$this->bannedTableAliases[] = $model->getTableAlias();
		return $this;
	}

	public function tpl($storeTo)
	{
		Locator::get('tpl')->setRef($storeTo, $this);
		return $this;
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
		return $this;
	}

	// ########## QUOTES ############## //
	public function quoteValue($value)
	{
		return (isset($value) ?  self::quote($value) : 'NULL');
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

	public function quoteFieldShort($name)
	{
		return $this->quoteName($name);
	}


	public function &load($where=NULL, $limit=NULL, $offset=NULL)
	{
		$this->notify('before_load', array(&$this));

		if ($this->pagerEnabled)
		{
			$total = $this->getCount($where);

			if ($total == 0)
			{
				return;
			}

			$pager = &$this->getPager();
			$pager->setup(RequestInfo::get($this->pagerVar), $total, $this->pagerPerPage, $this->pagerFrameSize);
			$limit = $pager->getLimit();
			$offset = $pager->getOffset();
		}

		$this->setData($this->select($where, $limit, $offset));

		$this->notify('load', array(&$this));

		return $this;
	}

	public function &loadOne($where = NULL)
	{
		$this->setOne(true);
		return $this->load($where, 1);
	}





	// ############# Internal realization ######################### //


	protected function initialize($fieldSet = null)
	{
		if (is_null($this->table))
		{
			$this->autoDefineTable();
		}

		//для всех классов с пустым fields
		$className = get_class($this);
		$configLoaded = false;
		if ( $className !== 'DBModel' )
		{
			$configLoaded = $this->loadConfig($className, $fieldSet);
		}

		if (!$configLoaded)
		{
			$this->addFields($this->fields);
		}
	}

	protected function &getPager()
	{
		if (null === $this->pager)
		{
			$this->pager = new Pager();
		}

		return $this->pager;
	}

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
		$config['initialized'] = false;

		$this->foreignFields[$fieldName] = $config;
	}


	/**
	 * Try to determine table name from class name
	 *
	 * @return string
	 */
	protected function autoDefineTable($className="")
	{
		if (!$className)
		$className = get_class($this);

		if ($className == 'DBModel')
		{
			return NULL;
		}

		$this->setTable( Inflector::underscore(str_replace(array("Model", "Basic"), "", $className)) );
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

		$where = $model->quoteField($fieldinfo['fk']) .'='. DBModel::quote($data[$fieldinfo['pk']]);
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

		$qt = $this->quoteName(DBAL::$prefix.$fieldinfo['through']['table']);

		$sqlParts = $model->getSqlParts();
		$sqlParts['join'] .= '
			INNER JOIN '.$qt.' AS '.$qt.'
			ON
			(
				('.$model->quoteField($fieldinfo['fk']).'='.$qt.'.'.$this->quoteName($fieldinfo['through']['fk']).')
				 AND
				('.$qt.'.'.$this->quoteName($fieldinfo['through']['pk']) .'='. DBModel::quote($data[$fieldinfo['pk']]).')
			) ';

		$model->loadSql(implode(' ', $sqlParts));
		$data[$fieldName] = &$model;
	}

	protected function mapHasOne($fieldName, &$data)
	{
		$fieldinfo = &$this->foreignFields[$fieldName];
		$model = &$this->getForeignModel($fieldName);

		if (!isset($model)) return;

		$where = $model->quoteField($fieldinfo['fk']) .'='. DBModel::quote($data[$fieldinfo['pk']]);
		$model->load($where);

		$data[$fieldName] = &$model;
	}


	/**
	 * Загрузить данные о файлах из аплоада
	 *
	 **/
	protected function mapUpload($fieldName, &$data)
	{
		$model =& Locator::get('upload');
		if (!isset($model)) return;

		$fieldinfo = &$this->foreignFields[$fieldName];
		
		$fname = str_replace('*', $data['id'], $fieldinfo['path']);

		$file = $model->getFile($fname);
		if ($file)
		{
			list($width, $height, $type, $attr) = getimagesize($file->name_full);
			$file->height = $height;
			$file->width = $width;
		}
		$data[$fieldName] = $file;
	}

	/**
	 * Строит join для has_one
	 *
	 * @return string
	 **/
	public function buildJoin()
	{
		$joinSql = '';
		$fieldsSql = '';
		$whereSql = '';

		$allowedTypes = array('has_one');

		foreach ($this->foreignFields AS &$v)
		{
			if (!in_array($v['type'], $allowedTypes) || (isset($v['lazy_load']) && $v['lazy_load']))
			{
				continue;
			}

			$foreignModel = &$this->getForeignModel($v['name']);

			if (!$foreignModel)
			{
				continue;
			}

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
			. $foreignModel->getTableNameWithAlias()
			.	" ON ("
			.		  $where
			.	     ")"
			;

			$fieldsSql .= "," . $foreignModel->getFieldsForJoin();


			// foreign joins
			$foreignSql = $foreignModel->buildJoin();
			$joinSql .= $foreignSql[0];
			$fieldsSql .= $foreignSql[1];
			$whereSql .= (($foreignSql[2] && $whereSql) ? " AND " : "") . $foreignSql[2];
		}

		return array($joinSql, $fieldsSql, $whereSql);
	}

	public function getSqlParts($where=NULL, $limit=NULL, $offset=NULL)
	{
		if (!empty($this->sqlParts))
		{
			$this->sqlParts['order'] = $this->buildOrderBy($this->order);
			$this->sqlParts['limit'] = $this->buildLimit($limit, $offset);

			return $this->sqlParts;
		}

		$this->sqlParts = array();

		$this->sqlParts['fields'] = 'SELECT '.$this->getFields();
		$this->sqlParts['from'] = 'FROM '.$this->getTableNameWithAlias();

		list($joinSql, $joinFields, $joinWhere) = $this->buildJoin();
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



	public function selectSql($sql, $isLoad=false)
	{
		$data = Locator::get('db')->query($sql);

		if ($data !== null)
		{
			$foreignData = $this->divideForeignDataFrom($data);
			$this->applyDataToForeignModels($foreignData, $data);
		}

		return $data;
	}

	/**
	 * Отделение данных внешних моделей от данных текущей модели
	 *
	 * @param array $data
	 * @return array
	 */
	protected function divideForeignDataFrom(&$data)
	{
		$foreignData = array();

		if (!is_array($this->foreignFields) || empty($this->foreignFields) || empty($this->foreignModels))
		{
			return $foreignData;
		}

		foreach ($data AS $row => &$d)
		{
			foreach($d AS $fieldName => $fieldValue)
			{
				if (!isset($this->tableFields[$fieldName]))
				{
					$fieldParts = explode(':', $fieldName);
					if (!is_array($foreignData[$row][$fieldParts[0]]))
					{
						$foreignData[$row][$fieldParts[0]] = array();
					}
					$foreignData[$row][$fieldParts[0]][$fieldParts[1]] = $fieldValue;
					unset($d[$fieldName]);
				}
			}

			if (empty($foreignData))
			{
				return $foreignData;
			}
		}

		return $foreignData;
	}

	/**
	 * Заполнение данными внешних моделей
	 *
	 * @param array $data
	 * @param array || null $modelData
	 */
	protected function applyDataToForeignModels($data, &$modelData = null)
	{
		if (empty($data) || empty($this->foreignFields) || empty($this->foreignModels))
		{
			return;
		}

		if ($modelData === null)
		{
			$modelData = &$this->data;
		}

		foreach ($data AS $row => $valuesSet)
		{
			foreach ($valuesSet AS $tableAlias => $d)
			{
				if (isset($this->foreignAlias2FieldName[$tableAlias]))
				{
					$fieldName = $this->foreignAlias2FieldName[$tableAlias];
					$model = &$this->foreignModels[$fieldName];

					$clonedModel = clone $model;
					$d = array($d);

					$clonedModel->setOne(true);
					$clonedModel->applyDataToForeignModels(array($valuesSet), $d);
					$clonedModel->setData($d);
					$modelData[$row][$fieldName] = $clonedModel;
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
		if (isset($this->tableFields['_created']) && !isset($row['_created']))
		{
			$row['_created'] = date('Y-m-d H:i:s');
		}
	}

	protected function onBeforeUpdate(&$row)
	{
		if (isset($this->tableFields['_modified']) && !isset($row['_modified']))
		{
			$row['_modified'] = date('Y-m-d H:i:s');
		}
	}

	/**
	 * Добавление строчки в таблицу
	 *
	 * $row = array(
	 * 		'fieldName' => 'value',
	 * 		'fieldName' => 'value',
	 * 		........
	 * );
	 *
	 * @param array $row
	 * @return int inserted id
	 */
	public function insert(&$row)
	{
		$this->onBeforeInsert($row);

		$fields = implode(',', array_map(array(&$this, 'quoteName'), array_keys($row)));
		$values = implode(',', array_map(array(&$this, 'quoteValue'), $row));

		$sql = ' INSERT INTO '.$this->quoteName(DBAL::$prefix.$this->getTableName())
		.'('.$fields.')'
		.' VALUES ('.$values.')';

		$row['id'] = $this->db->insert($sql);
		return $row['id'];
	}


	public function update(&$row, $where=NULL)
	{
		$this->usePrefixedTableAsAlias = true;

		if ($where)
		{
			$where = ' WHERE '.$this->parse($where);
		}

		$this->onBeforeUpdate($row);

		$sql = ' UPDATE '.$this->quoteName(DBAL::$prefix.$this->getTableName())
		.' SET '.$this->getFieldsValuesSet($row)
		. $where;

		$this->usePrefixedTableAsAlias = false;

		return $this->db->query($sql);
	}

	public function delete($where)
	{
		$this->usePrefixedTableAsAlias = true;

		if ($where)
		{
			$where = 'WHERE '.$this->parse($where);
		}

		$sql = 'DELETE FROM '.$this->quoteName(DBAL::$prefix.$this->getTableName()).$where;

		$this->usePrefixedTableAsAlias = false;

		return $this->db->query($sql);
	}

	public function clean($truncate=True)
	{
		switch ($truncate)
		{
			case True:  $sql = ' TRUNCATE TABLE ' .$this->quoteName(DBAL::$prefix.$this->getTableName());
			break;
			case False: $sql = ' DELETE FROM ' .$this->quoteName(DBAL::$prefix.$this->getTableName());
			break;
			default:    $sql = NULL;
		}

		if (isset($sql)) return $this->db->query($sql);
		return False;
	}

	protected function buildLimit($limit=NULL, $offset=NULL)
	{
		$limit = isset($limit) ? $limit : $this->limit;
		$offset = isset($offset) ? $offset : $this->offset;
		if ($limit && $offset)
		$limit_sql = " LIMIT ".$offset.",".$limit;

		else if ($limit)
		$limit_sql = " LIMIT ".$limit;

		return $limit_sql;
	}

	/**
	 * Return `tableAlias`.`field_1`,`tableAlias`.`field_2` AS `AAAA`,`tableAlias`.`field_3` AS `BBBB`, etc...
	 *
	 * @return string
	 */
	protected function getFields()
	{
		return implode(', ', array_map(array(&$this, 'buildFieldAlias'), $this->tableFields));
	}

	/**
	 * Return `field_1`,`field_2`,`field_3`, etc...
	 *
	 * @return string
	 */
	protected function getShortFields()
	{
		//return implode(',', array_map(array(&$this, 'buildShortFieldAlias'), $this->tableFields));
	}

	public function getFieldsForJoin()
	{
		return implode(',', array_map(array(&$this, 'buildJoinFieldAlias'), $this->tableFields));
	}

	protected function getFieldsValuesSet($data)
	{
		$set = array();
		foreach ($data AS $k=>$v)
		$set[] = $this->quoteField($k) . '='. $this->quoteValue($v);
		return implode(',', $set);
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
		return " HAVING " . $this->parse($this->having);
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
				$sql .= $this->parse($fields);
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
		$str = preg_replace_callback('#{([^}]+)}#', array(&$this, 'parseCallback'), $str);
		return $str;
	}

	protected function parseCallback($matches)
	{
		$ret = $this->quoteField($matches[1]);
		return $ret;
	}

	public function &getForeignModel($fieldName)
	{
		if (!isset($this->foreignModels[$fieldName]))
		{
			$this->initForeignModel($fieldName);
		}
		elseif (!$this->foreignFields[$fieldName]['initialized'])
		{
			$this->initForeignModelConfig($fieldName);
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
		$model = & DBModel::factory($field['className']);
		$model->setBannedTableAliases($this->bannedTableAliases);
		//$this->bannedTableAliases[] = $model->getTableAlias();

		$this->foreignModels[$fieldName] = &$model;

		$this->initForeignModelConfig($fieldName);
	}

	protected function initForeignModelConfig($fieldName)
	{
		if (!isset($this->foreignFields[$fieldName]))
		{
			throw new Exception('config for model \''.get_class($this->foreignModels[$fieldName]).'\' doesn\'t exists');
		}
		$field = &$this->foreignFields[$fieldName];
		$model = &$this->foreignModels[$fieldName];
		//fk
		if (!isset($field["fk"]))
		{
			if ($field['type'] == "has_one" || $field['type'] == "many2many")
			{
				$field["fk"] = $model->getPk();
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

		$this->foreignAlias2FieldName[$model->getTableAlias()] = $fieldName;

		$field['initialized'] = true;
	}

	public function isForeignField($field)
	{
		if (isset($this->foreignFields[$field]))
		return true;
		else
		return false;
	}



	/*
	 * Реализация интерфейсов IteratorAggregate, ArrayAccess, Countable
	 *
	 */

	public function haveData()
	{
		return is_array($this->data);
	}

	//implements IteratorAggregate
	public function getIterator()
	{
		if (null === $this->data)
		{
			return new ArrayIterator(array());
		}

		if ($this->one)
		{
			return new ArrayIterator($this->data[0]);
		}
		else
		{
			return new ArrayIterator($this->data);
		}
	}

	//implements ArrayAccess
	public function offsetExists($key)
	{
		return $this->haveData() && ($this->one ? isset($this->data[0][$key]) : isset($this->data[$key]));
	}

	public function offsetGet($key)
	{
		if (!$this->haveData())
		{
			return null;
		}

		if ($this->one)
		{
			if (isset($this->data[0]))
			{
				return $this->data[0][$key];
			}
			else
			{
				return null;
			}
		}
		else
		{
			if (isset($this->data[$key]))
			{
				return $this->data[$key];
			}
			else
			{
				return null;
			}
		}
	}

	public function offsetSet($key, $value)
	{
		if (NULL === $key)
		{
			if ($this->one)
			{
				$this->data[0][] = $value;
			}
			else
			{
				$this->data[] = $value;
			}
		}
		else
		{
			if ($this->one)
			{
				$this->data[0][$key] = $value;
			}
			else
			{
				$this->data[$key] = $value;
			}
		}
	}

	public function offsetUnset($key)
	{
		if ($this->one)
		{
			unset($this->data[0][$key]);
		}
		else
		{
			unset($this->data[$key]);
		}
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
	}

	?>
