<?php

/*
  ���������� �� ���������� ����:
  * ��������� ������ sql-��������
  * ������ ��������� �� sql-oriented violations

  DBAL( &$rh, $connect=true )

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

  $rh->db_host     = "localhost"
  $rh->db_user     = "username"
  $rh->db_password = "pwd"
  $rh->db_name     = "project_db"
  $rh->db_prefix   = "prefix_"

  $rh->db_al       = "mysql" <- �� ���� ����� �� �����, ����� ���� ��� ��� �����

  ---------

  �������� ��������������� � ����� ����� ������ �������� ��� ($db -- ��� ���������� ������):

  $a = $db->Query( "select * from ".$db->prefix." where active=1 and section_id=". $db->Quote(35) );
  echo $a[0]["section_id"];

=============================================================== v.2 (kuso@npj)
*/

class DBAL
{
	function &getInstance(& $rh = null, $connect = true) //���������� �����������
	{
		static $instance = null;
		if (null === $instance) 
		{
			$instance = new DBAL($rh, $connect); 
		}
		return $instance;
	}

	var $rh; // use: $this->rh->debug->Trace (..->Error)
	var $lowlevel;
	var $queryCount = 0;

	/*
	// NB: �� ����������� � ������ ������ (ForR2, ?????)
	var $active_from = "active=1";        // �� ���� � �� ��� ���������� ������ "���������"
	var $active_to   = "active=active";   // ����-�� � �� ��� ����������������� ������������
	var $active_role = "editor";          // ��� ����� "����" ��� ������� ������ (���� ����� �� �������)
	*/

	function &DBAL(& $rh, $connect = true)
	{
		$this->rh = & $rh;
		$this->prefix = $rh->db_prefix;

		// ������� �������������� ����
		require_once (dirname(__FILE__) . "/DBAL_" . $rh->db_al . ".php");
		$lowlevel_name = "DBAL_" . $rh->db_al;
		$lowlevel = & new $lowlevel_name ($this);
		$this->lowlevel = & $lowlevel;

		// connection, if any
		if ($connect)
			$this->_Connect();
	}

	function _Connect()
	{
		$this->lowlevel->Connect();
	}

	function Close()
	{
		$this->lowlevel->Close();
	}

	// ������ ���������� �������� ���������
	function Quote($value)
	{
		return $this->lowlevel->Quote($value);
	}

	// ��������� ������� SQL � ��������� � ���� �����
	function Query($sql, $limit = 0, $offset = 0)
	{
		// #1. patch sql for active
		// �� ����������� � ������ ������ (ForR2, ?????)
		// #2. query
		return $this->_Query($sql, $limit, $offset);
	}

	function _Query($sql, $limit = 0, $offset = 0)
	{
		Debug::mark('q');

		$data = array ();
		//����������� ��� ��������
		$sql = str_replace("??", $this->prefix, $sql);
		
		if ($r = $this->lowlevel->Query($sql, $limit, $offset))
		{
			while ($row = $this->lowlevel->FetchAssoc($r))
			{
				//$data[] = $row;
				if (is_string($limit))
                    $data[$row[$limit]] = $row;
                else
                    $data[] = $row;
			}
			$this->lowlevel->FreeResult($r);
		} 
				
		$this->logQuery($sql, $limit, $offset);
		return $data;
	}

	function logQuery($sql, $limit = 0, $offset = 0)
	{
		$this->queryCount++;
		
		if($this->rh->enable_debug && $this->rh->explain_queries)	
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

	function QueryOne($sql)
	{
		// #1. launch Query
		$res = $this->Query($sql, 1);
		// #2. get 1st
		if (sizeof($res))
			return $res[0];
		else
			return false;
	}

	function Insert($sql)
	{
		$res = $this->lowlevel->Query($sql);
		return $this->lowlevel->InsertId();
	}

	function AffectedRows()
	{
		return $this->lowlevel->AffectedRows();
	}

	// ������� ���-�� ������� � �������
	function RecordCount($sql)
	{
		// ????? take a look on regexp pls.
		$sql = preg_replace("/^select.*?((\s|\().*?(\s|\)))from/i", "select count(*) as _row_number from ", $sql);
		$res = $this->QueryOne($sql);
		if ($res)
			return $res["_row_number"];
		else
			return 0;
	}

	/**
	 *  ��������� ������, ���������� ������ �� ���������
	 *  ��������������� ��� ������������� getRow, getObject, getArray
	 */
	function Execute($sql, $limit = 0, $offset = 0)
	{
		//���� ����� �����������
		$sql = str_replace("??", $this->prefix, $sql);
		Debug::mark('q');
		
		$this->handle = $this->lowlevel->Query($sql, $limit, $offset);
		$this->logQuery($sql, $limit, $offset);

		if ($this->handle)
		{
			$this->numRows = $this->lowlevel->getNumRows($this->handle);
			$this->currentRow = 0;
			return $this->handle; //;$this->numRows
		}

		return null;
	}

	/**
	 * ���������� ������� �������� ������� ������
	 */
	function getObject($class_name = null)
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
	function getRow($resultId = null)
	{
		if($row = $this->lowlevel->FetchAssoc($resultId ? $resultId : $this->handle))
		{
			return $row;
		}
		else
		{
			$this->lowlevel->FreeResult($resultId ? $resultId : $this->handle);
			return false;
		}
	}

	/**
	 * ���������� ��� ������� ���������� ������
	 * ������-�� ��� ��� �������
	 */
	function getArray()
	{
		//var_dump($this->handle);  

		if ($this->handle)
		{
			while ($row = $this->getRow())
			{
				//echo '<hr>';
				// var_dump($row);  
				$ret[] = $row;
			}

			return $ret;
		}
	}

	/**
	 * for osb
	 */
	function insert_id()
	{
		return $this->lowlevel->InsertId();
	}

	function SelectLimit($sql, $limit, $offset)
	{
		return $this->execute($sql, $limit, $offset);
	}
	// EOC{ DBAL } 
}
?>