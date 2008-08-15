<?php

/*
  Абстракция от конкретной СУБД:
  * упрощение вызова sql-запросов
  * защита кавычками от sql-oriented violations

  DBAL($connect=true )

  ---------

  NB: вместо $rh можно передавать любой объект, содержащий свойства $this->db_*
      и опционально $this->debug (объект класса Debug)

  Этот класс -- верхний уровень абстракции от СУБД.
  Для конкретных СУБД реализован нижний уровень -- DBAL_****, через $rh->db_al="****"
  Файлы должны лежать в одном каталоге.

  ---------

  // Защита строкового значения кавычками

  * Quote( $value ) -- обквочивает значение, делая его безопасным для SQL
      - возвращает что-то вроде '13' или 'строка'
        (вместе с правильными кавычками, которые вокруг добавлять уже не надо)

  // Упрощение вызовов SQL с возвратом в виде хэшей

  * Query( $sql, $limit=0, $offset=0 ) -- самый навороченный, используется просто ВСЕГДА
      - делает предобработку запроса (если пользователь -- админ, то "выключает" из запроса "active=1")
      - получает результат запроса
      - выкладывает его в массив из ассоциированных массивов (хэшей)
  * _Query( $sql, $limit=0, $offset=0 ) -- то же самое, только без предобратки
  * QueryOne( $sql ) -- то же, что Query, но берёт из БД и возвращает только первую запись хэшем
                        или false, если результат -- пустота.

  * Insert( $sql ) -- запрос типа INSERT, возвращаем идентификатор вставленной записи

  // Прочее

  * RecordCount( $sql ) -- чтобы не вызывать Query для этой задачи, возвращает число
  * Close() -- лучше бы вызывать в конце, а то мало ли что мы тут "напридумывали" в плане освобождения памяти
  * _Connect() -- присоединяется к БД, получая рукоятку. Вызывается из конструктора, если не указан спецпараметр

  ---------

  Типовые DBAL-параметры в $rh:

  db_host     = "localhost"
  db_user     = "username"
  db_password = "pwd"
  db_name     = "project_db"
  db_prefix   = "prefix_"

  db_al       = "mysql" <- по этой штуке мы ловим, какой дбал нам был нужен

  ---------

  Типичный приспособленный к этому дбалу запрос выглядит так ($db -- это дбаловский объект):

  $a = $db->Query( "select * from ".$db->prefix." where active=1 and section_id=". $db->Quote(35) );
  echo $a[0]["section_id"];

=============================================================== v.2 (kuso@npj)
*/

class DBAL
{
	private static $instance = null;
	public static $prefix = '';

	protected $lowlevel;
	protected $queryCount = 0;

	private function __construct($connect = true)
	{
		DBAL::$prefix = Config::get('db_prefix');

		$lowlevelClass = "DBAL_" . Config::get('db_al');
		Finder::useClass($lowlevelClass);

		$this->lowlevel = & new $lowlevelClass();

		// connection, if any
		if ($connect)
		{
			$this->connect();
		}
	}

	/**
	 * singletone
	 *
	 * @param RequestHandler $rh
	 * @param string $connect
	 * @return DBAL
	 */
	public static function &getInstance($connect = true)
	{
		if (null === self::$instance)
		{
			self::$instance = new DBAL($connect);
		}
		return self::$instance;
	}
	
	public function connect()
	{
		$this->lowlevel->connect();
	}

	public function close()
	{
		$this->lowlevel->Close();
	}

	// Защита строкового значения кавычками
	public function quote($value)
	{
		return $this->lowlevel->quote($value);
	}

	public function query($sql, $limit = 0, $offset = 0)
	{
		$resultId = $this->execute($sql, $limit, $offset);

		if (is_string($limit))
			$key = $limit;
		else
			$key = null;

		return $this->getArray($resultId, $key);
	}

	public function queryOne($sql)
	{
		// #1. launch Query
		$res = $this->query($sql, 1);
		// #2. get 1st
		if (count($res))
			return $res[0];
		else
			return false;
	}

	public function insert($sql)
	{
		$sql = $this->prepareSql($sql);
		$res = $this->lowlevel->query($sql);
		return $this->lowlevel->insertId();
	}

	public function insertId()
	{
		return $this->lowlevel->insertId();
	}

	public function affectedRows()
	{
		return $this->lowlevel->affectedRows();
	}

	/**
	 *  выполняет запрос, запоминает ссылку на результат
	 *  подразумевается для использования getRow, getObject, getArray
	 */
	public function execute($sql, $limit = 0, $offset = 0)
	{
		Debug::mark('q');

		$sql = $this->prepareSql($sql);
//		$sql = str_replace("??", DBAL::$prefix, $sql);

		$this->handle = $this->lowlevel->query($sql, $limit, $offset);
		$this->logQuery($sql, $limit, $offset);

		return $this->handle;
	}

	public function getNumRows($resultId = null)
	{
		$resultId = $resultId ? $resultId : $this->handle;
		return $this->lowlevel->getNumRows($resultId);
	}

	/**
	 * возвращает строчку объектом нужного класса
	 */
	public function getObject($class_name = null)
	{
		if ($this->handle && $this->currentRow < $this->numRows)
		{
			$ret = $this->lowlevel->FetchObject($this->handle, $class_name);
			$this->currentRow++;
		} else
		{
			$this->lowlevel->FreeResult($this->handle);
			$ret = null;
		}
		return $ret;
	}

	/**
	 * возвращает строчку массивом
	 */
	public function getRow($resultId = null)
	{
		$resultId = $resultId ? $resultId : $this->handle;
		if($row = $this->lowlevel->fetchAssoc($resultId))
		{
			return $row;
		}
		else
		{
			$this->lowlevel->freeResult($resultId);
			return false;
		}
	}

	/**
	 * возвращает все строчки результата массом
	 */
	public function getArray($resultId = null, $key = null)
	{
		$resultId = $resultId ? $resultId : $this->handle;

		if ($resultId)
		{
			$data = array();

			while ($row = $this->getRow($resultId))
			{
				if ($key !== null)
					$data[$row[$key]] = $row;
				else
					$data[] = $row;
			}

			if (!empty($data))
			{
				return $data;
			}
		}

		return null;
	}

	public function prepareSql($sql)
	{
		return preg_replace('/((update|((insert|replace)\s*?(low_priority|delayed|)\s*?(into|))|from|join)\s*?)(`|)(\?\?)([a-zA-Z0-9_\-]+)([\s(`]{1})/i', '$1'.DBAL::$prefix.'$9$10', $sql, -1, $count);
	}

	protected function logQuery($sql, $limit = 0, $offset = 0)
	{
		$this->queryCount++;

		if(Config::get('enable_debug') && Config::get('explain_queries'))
		{
			if(!(strpos(strtolower($sql), 'select') === false))
			{
				$_data = array();
				if ($r = $this->lowlevel->Query("EXPLAIN ".$sql, $limit, $offset))
				{
					while ($row = $this->lowlevel->FetchAssoc($r))
					{
						$_data[] = $row;
					}
					$this->lowlevel->FreeResult($r);
				}
				if(is_array($_data) && !empty($_data))
				{
					foreach($_data AS $i => $r)
					{
						if($i == 0)
						{
							$head = array_keys($r);
							foreach($head AS $rr)
							{
								$out .= "<td>".$rr."</td>";
							}
							$out = "<tr>".$out."</tr>";
						}

						foreach($r AS $rr)
						{
							$row .= "<td>".$rr."</td>";
						}
						$out .= "<tr>".$row."</tr>";
						$row = '';
					}

					$out = "<table>".$out."</table>";
				}
				$bad = 0;
				if(!(stripos($out, 'filesort') === false) || !(stripos($out, 'temporary') === false))
				{
					$bad = 1;
				}
				Debug::trace("<b><span ".($bad ? "style='color: red;'" : "").">QUERY</span>".($limit == 1 ? " ONE: " : ": ")."</b> ".$sql, 'db', 'q', $out);
				return;
			}
		}

		Debug::trace("<b>QUERY".($limit == 1 ? " ONE: " : ": ")."</b> ".$sql, 'db', 'q');
	}
	// EOC{ DBAL }
}
?>