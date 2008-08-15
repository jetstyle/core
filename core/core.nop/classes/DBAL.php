<?php

/*
  ���������� �� ���������� ����:
  * ��������� ������ sql-��������
  * ������ ��������� �� sql-oriented violations

  DBAL($connect=true )

  ---------

  NB: ������ $rh ����� ���������� ����� ������, ���������� �������� $this->db_*
      � ����������� $this->debug (������ ������ Debug)

  ���� ����� -- ������� ������� ���������� �� ����.
  ��� ���������� ���� ���������� ������ ������� -- DBAL_****, ����� $rh->db_al="****"
  ����� ������ ������ � ����� ��������.

  ---------

  // ������ ���������� �������� ���������

  * Quote( $value ) -- ����������� ��������, ����� ��� ���������� ��� SQL
      - ���������� ���-�� ����� '13' ��� '������'
        (������ � ����������� ���������, ������� ������ ��������� ��� �� ����)

  // ��������� ������� SQL � ��������� � ���� �����

  * Query( $sql, $limit=0, $offset=0 ) -- ����� ������������, ������������ ������ ������
      - ������ ������������� ������� (���� ������������ -- �����, �� "���������" �� ������� "active=1")
      - �������� ��������� �������
      - ����������� ��� � ������ �� ��������������� �������� (�����)
  * _Query( $sql, $limit=0, $offset=0 ) -- �� �� �����, ������ ��� �����������
  * QueryOne( $sql ) -- �� ��, ��� Query, �� ���� �� �� � ���������� ������ ������ ������ �����
                        ��� false, ���� ��������� -- �������.

  * Insert( $sql ) -- ������ ���� INSERT, ���������� ������������� ����������� ������

  // ������

  * RecordCount( $sql ) -- ����� �� �������� Query ��� ���� ������, ���������� �����
  * Close() -- ����� �� �������� � �����, � �� ���� �� ��� �� ��� "�������������" � ����� ������������ ������
  * _Connect() -- �������������� � ��, ������� ��������. ���������� �� ������������, ���� �� ������ ������������

  ---------

  ������� DBAL-��������� � $rh:

  db_host     = "localhost"
  db_user     = "username"
  db_password = "pwd"
  db_name     = "project_db"
  db_prefix   = "prefix_"

  db_al       = "mysql" <- �� ���� ����� �� �����, ����� ���� ��� ��� �����

  ---------

  �������� ��������������� � ����� ����� ������ �������� ��� ($db -- ��� ���������� ������):

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

	// ������ ���������� �������� ���������
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
	 *  ��������� ������, ���������� ������ �� ���������
	 *  ��������������� ��� ������������� getRow, getObject, getArray
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
	 * ���������� ������� �������� ������� ������
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
	 * ���������� ������� ��������
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
	 * ���������� ��� ������� ���������� ������
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